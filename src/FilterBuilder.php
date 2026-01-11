<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Psr\Http\Message\RequestInterface;

class FilterBuilder
{
    /** @var array<string, string> */
    protected array $filters = [];

    public function __construct(
        protected RequestInterface $request,
    ) {}

    public static function for(RequestInterface $request): self
    {
        return new self($request);
    }

    /**
     * Add a filter. The key format is entirely up to the developer.
     *
     * Examples:
     *   ->where('name', 'john')
     *   ->where('filter[status]', 'active')
     *   ->where('price_gte', 100)
     * @param  string  $key
     * @param  mixed   $value
     */
    public function where(string $key, mixed $value): self
    {
        $this->filters[$key] = (string) $value;

        return $this;
    }

    /**
     * Conditionally apply filters.
     * @param  mixed     $condition
     * @param  callable  $callback
     */
    public function when(mixed $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get all filters without applying.
     *
     * @return array<string, string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Apply all filters to the request and return the modified request.
     */
    public function apply(): RequestInterface
    {
        $uri = $this->request->getUri()->withQuery(http_build_query($this->filters));

        return $this->request->withUri($uri, preserveHost: true);
    }
}
