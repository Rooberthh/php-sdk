<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use BackedEnum;
use DateTimeInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Stringable;
use UnitEnum;

/**
 * @phpstan-consistent-constructor
 */
class QueryBuilder
{
    /** @var array<string, mixed> */
    protected array $filters = [];

    public function __construct(
        protected RequestInterface $request,
    ) {}

    public static function for(RequestInterface $request): static
    {
        return new static($request);
    }

    /**
     * Add a filter. The key format is entirely up to the developer.
     *
     * Examples:
     *   ->where('name', 'john')
     *   ->where('filter[status]', 'active')
     *   ->where('price_gte', 100)
     *
     * Values are normalised when the query string is built, not here:
     * booleans become "true"/"false", backed enums become their value,
     * dates become ATOM strings, and null filters are omitted entirely.
     * @param string $key
     * @param mixed $value
     */
    public function where(string $key, mixed $value): static
    {
        $this->filters[$key] = $value;

        return $this;
    }

    /**
     * Add several filters at once. Null values are omitted when applied,
     * so an array of optional filters can be passed straight through.
     *
     * @param array<string, mixed> $filters
     */
    public function whereAll(array $filters): static
    {
        foreach ($filters as $key => $value) {
            $this->where($key, $value);
        }

        return $this;
    }

    /**
     * Get all filters as they were given, without normalisation.
     *
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Build the query string these filters produce, merged over any query
     * parameters already present on the request.
     */
    public function toQueryString(): string
    {
        $params = $this->mergeParams(
            $this->expand($this->request->getUri()->getQuery()),
            $this->expand(http_build_query($this->normalise($this->filters), encoding_type: PHP_QUERY_RFC3986)),
        );

        return http_build_query($params, encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * Apply all filters to the request and return the modified request.
     */
    public function apply(): RequestInterface
    {
        $uri = $this->request->getUri()->withQuery($this->toQueryString());

        return $this->request->withUri($uri, preserveHost: true);
    }

    /**
     * Parse a query string into a nested array, so that bracketed keys on
     * either side ("filter[status]") collide correctly when merged.
     *
     * @param string $query
     * @return array<array-key, mixed>
     */
    protected function expand(string $query): array
    {
        parse_str($query, $params);

        return $params;
    }

    /**
     * Merge filters over existing parameters. Nested maps merge key by key;
     * lists and scalars are replaced outright.
     *
     * @param  array<array-key, mixed> $base
     * @param  array<array-key, mixed> $overrides
     * @return array<array-key, mixed>
     */
    protected function mergeParams(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $existing = $base[$key] ?? null;

            $base[$key] = is_array($value) && is_array($existing) && !array_is_list($value)
                ? $this->mergeParams($existing, $value)
                : $value;
        }

        return $base;
    }

    /**
     * @param  array<array-key, mixed> $filters
     * @return array<array-key, mixed>
     */
    protected function normalise(array $filters): array
    {
        $normalised = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalised[$key] = is_array($value)
                ? $this->normalise($value)
                : $this->normaliseValue((string) $key, $value);
        }

        return $normalised;
    }

    protected function normaliseValue(string $key, mixed $value): string|int|float
    {
        return match (true) {
            is_bool($value)                  => $value ? 'true' : 'false',
            $value instanceof BackedEnum     => $value->value,
            $value instanceof UnitEnum       => $value->name,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_int($value), is_float($value), is_string($value) => $value,
            $value instanceof Stringable     => (string) $value,
            default => throw new InvalidArgumentException(sprintf(
                'Filter [%s] cannot be converted to a query parameter, got %s.',
                $key,
                get_debug_type($value),
            )),
        };
    }
}
