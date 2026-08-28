<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Support;

/**
 * Reads a value out of a decoded body by dot path.
 *
 * A vendored copy of the one function this package needs from Laravel's
 * `Arr::get`, kept here rather than pulled in as a dependency — an SDK
 * framework has no business dragging illuminate/support into someone else's
 * project for a single lookup.
 *
 * @internal
 */
final class Arr
{
    private function __construct() {}

    /**
     * The value at a dot path, or the default when the path leads nowhere.
     *
     * A key is tried whole before it is split, so a body that really does
     * hold a "meta.total" key stays reachable instead of being shadowed by
     * the path that happens to spell the same thing. There is no escape
     * syntax beyond that: a dotted key nested deeper than the top level
     * cannot be addressed, which matches every other dot-path helper in the
     * ecosystem.
     *
     * @param  array<mixed, mixed> $array
     * @param  null|string|int     $key
     * @param  mixed               $default
     * @return mixed
     */
    public static function get(array $array, string|int|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        // array_key_exists rather than isset, so a null the body actually
        // holds is returned as null instead of being mistaken for absence.
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        // An int key can never be a path, and str_contains would reject it
        // outright under strict_types.
        if (is_int($key) || ! str_contains($key, '.')) {
            return $default;
        }

        $value = $array;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
