<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Uri\Rfc3986\Uri as Rfc3986Uri;

/**
 * An RFC 3986 URI reference, parsed and resolved by PHP's own
 * `Uri\Rfc3986\Uri`.
 *
 * It is a thin, immutable wrapper so the rest of the package depends on one
 * small surface rather than on the native class directly. Every getter returns
 * the normalised form: scheme and host lowercased, percent-encoded unreserved
 * characters decoded, and dot segments removed on resolution.
 */
final readonly class Uri
{
    private function __construct(
        private Rfc3986Uri $uri,
    ) {}

    /**
     * Parses a URI reference, resolving it against `$base` when one is given.
     * Null when it isn't valid RFC 3986 (a space, a stray `%`).
     *
     * @param string    $uri
     * @param null|self $base
     */
    public static function parse(string $uri, ?self $base = null): ?self
    {
        $parsed = Rfc3986Uri::parse($uri, $base?->uri);

        return $parsed === null ? null : new self($parsed);
    }

    public function getScheme(): ?string
    {
        return $this->uri->getScheme();
    }

    public function getUserInfo(): ?string
    {
        return $this->uri->getUserInfo();
    }

    public function getHost(): ?string
    {
        return $this->uri->getHost();
    }

    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    public function getQuery(): ?string
    {
        return $this->uri->getQuery();
    }

    public function getFragment(): ?string
    {
        return $this->uri->getFragment();
    }

    /**
     * @param string $path
     */
    public function withPath(string $path): self
    {
        return new self($this->uri->withPath($path));
    }

    /**
     * @param null|string $query
     */
    public function withQuery(?string $query): self
    {
        return new self($this->uri->withQuery($query));
    }

    /**
     * @param self $other
     */
    public function equals(self $other): bool
    {
        return $this->uri->equals($other->uri);
    }

    public function toString(): string
    {
        return $this->uri->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
