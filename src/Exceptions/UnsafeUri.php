<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a base URI or a request path could send a request, and its
 * credentials, somewhere other than the API the Client was made for.
 */
final class UnsafeUri extends InvalidArgumentException
{
    public static function notAbsolute(string $uri): self
    {
        return new self("The base URI must be absolute, with a scheme and a host: [{$uri}].");
    }

    public static function baseHasQueryOrFragment(string $uri): self
    {
        return new self("The base URI can't carry a query or fragment: [{$uri}].");
    }

    public static function baseHasCredentials(): self
    {
        return new self("The base URI can't carry credentials; use an Authentication strategy.");
    }

    public static function pathHasQueryOrFragment(string $path): self
    {
        return new self("A request path can't carry a query or fragment; pass the query separately: [{$path}].");
    }

    public static function pathNotRelative(string $path): self
    {
        return new self("A request path must be relative to the base URI: [{$path}].");
    }

    public static function pathHasDotSegments(string $path): self
    {
        return new self("A request path can't contain `..` segments: [{$path}].");
    }

    public static function invalidPath(string $path): self
    {
        return new self("Not a valid request path: [{$path}].");
    }

    public static function pathLeavesBase(string $path): self
    {
        return new self("The request path leaves the base URI: [{$path}].");
    }
}
