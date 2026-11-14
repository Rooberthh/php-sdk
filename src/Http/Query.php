<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Rooberthh\Sdk\Contracts\HasQuery;
use Rooberthh\Sdk\Exceptions\InvalidQuery;

/**
 * A {@see HasQuery} Request's parameters, encoded as a URI query string.
 *
 * APIs disagree on how a list goes in a query, so the encoding picks none of
 * the conventions and lets the key decide: a list repeats its key
 * (`tags=a&tags=b`), so `tags[]` as the key gives the bracket style, and
 * values joined by the Request (`implode(',', $tags)`) give the comma style.
 * Null values are left out, booleans are sent as `true` and `false`, and
 * keys and values are percent-encoded per RFC 3986 (a space is `%20`).
 */
final readonly class Query
{
    private string $encoded;

    /**
     * @param array<string, null|bool|float|int|list<null|bool|float|int|string>|string> $params
     *
     * @throws InvalidQuery when a value is an array that isn't a list
     */
    public function __construct(array $params)
    {
        $pairs = [];

        foreach ($params as $name => $value) {
            // The type says list, but an untyped caller can pass a map, which
            // would otherwise go out with its keys silently dropped.
            // @phpstan-ignore function.alreadyNarrowedType, booleanAnd.alwaysFalse
            if (is_array($value) && !array_is_list($value)) {
                throw InvalidQuery::notAList((string) $name);
            }

            foreach (is_array($value) ? $value : [$value] as $part) {
                if ($part !== null) {
                    $pairs[] = rawurlencode((string) $name) . '=' . rawurlencode(self::format($part));
                }
            }
        }

        $this->encoded = implode('&', $pairs);
    }

    /**
     * Empty when every value was null, so no `?` needs to be sent.
     */
    public function isEmpty(): bool
    {
        return $this->encoded === '';
    }

    public function toString(): string
    {
        return $this->encoded;
    }

    /**
     * @param bool|float|int|string $value
     */
    private static function format(bool|float|int|string $value): string
    {
        return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
    }
}
