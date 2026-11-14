<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Rooberthh\Sdk\Exceptions\UnsafeUri;

/**
 * The one address a Client talks to, and the only one.
 *
 * Request paths are appended to the base path, so `/applications` under
 * `https://api.example.com/v2` is `/v2/applications`. Plain RFC 3986
 * resolution would drop the `/v2`, and would let `//elsewhere.com` or an
 * absolute URL send the request, and its credentials, to another host. A
 * path that would leave the base after normalisation is refused.
 */
final readonly class BaseUri
{
    private Uri $uri;

    /**
     * @param string $uri
     * @throws UnsafeUri when it is not an absolute http(s)-style URI, or carries a query, fragment or credentials
     */
    public function __construct(string $uri)
    {
        $parsed = Uri::parse($uri);

        if (!$parsed instanceof Uri || $parsed->getScheme() === null || $parsed->getHost() === null || $parsed->getHost() === '') {
            throw UnsafeUri::notAbsolute($uri);
        }

        if ($parsed->getQuery() !== null || $parsed->getFragment() !== null) {
            throw UnsafeUri::baseHasQueryOrFragment($uri);
        }

        if ($parsed->getUserInfo() !== null) {
            throw UnsafeUri::baseHasCredentials();
        }

        $path = $parsed->getPath();

        $this->uri = $parsed->withPath(str_ends_with($path, '/') ? $path : "{$path}/");
    }

    /**
     * The full URI for a request path, which is always under this base.
     *
     * @param string $path
     * @throws UnsafeUri when the path would leave the base
     */
    public function resolve(string $path): Uri
    {
        if (str_contains($path, '?') || str_contains($path, '#')) {
            throw UnsafeUri::pathHasQueryOrFragment($path);
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) === 1 || str_starts_with($path, '//')) {
            throw UnsafeUri::pathNotRelative($path);
        }

        // Normalisation decodes `%2e%2e` to `..` before removing dot segments,
        // so encoded ones would walk up too; they are refused with the literal ones.
        if (in_array('..', array_map(rawurldecode(...), explode('/', $path)), true)) {
            throw UnsafeUri::pathHasDotSegments($path);
        }

        $resolved = Uri::parse(ltrim($path, '/'), $this->uri);

        if (!$resolved instanceof Uri) {
            throw UnsafeUri::invalidPath($path);
        }

        if (!$this->contains($resolved)) {
            throw UnsafeUri::pathLeavesBase($path);
        }

        return $resolved;
    }

    /**
     * Whether an absolute URI, such as a `next` link, is under this base.
     *
     * @param string $uri
     */
    public function allows(string $uri): bool
    {
        $parsed = Uri::parse($uri);

        return $parsed instanceof Uri && $this->contains($parsed);
    }

    public function scheme(): string
    {
        return (string) $this->uri->getScheme();
    }

    public function toString(): string
    {
        return $this->uri->toString();
    }

    /**
     * @param Uri $uri
     */
    private function contains(Uri $uri): bool
    {
        return $uri->getScheme() === $this->uri->getScheme()
            && $uri->getHost() === $this->uri->getHost()
            && $uri->getPort() === $this->uri->getPort()
            && $uri->getUserInfo() === null
            && str_starts_with($uri->getPath(), $this->uri->getPath());
    }
}
