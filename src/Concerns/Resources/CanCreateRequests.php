<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\ClientContract;

/**
 * Lets a Resource build its requests as plain PSR-7, against its Client's URL.
 *
 * Headers and anything else go on with the usual `with…()` methods, and the
 * request is sent with `$this->client->send()`. It exists for SDK Authors to
 * compose into their own Resources, so nothing inside src/ uses it.
 *
 * @property-read ClientContract $client
 *
 * @phpstan-ignore trait.unused
 */
trait CanCreateRequests
{
    /**
     * @param Method               $method
     * @param string               $uri
     * @param array<string, mixed> $query null values are dropped
     */
    protected function request(Method $method, string $uri, array $query = []): RequestInterface
    {
        return $this->client->createRequest($method, $uri, $query);
    }

    /**
     * A body for {@see RequestInterface::withBody()}: a Payload object becomes
     * snake_case JSON with null properties dropped, an array is JSON-encoded as
     * given, and a string is sent raw.
     *
     * @param array<mixed, mixed>|object|string $body
     */
    protected function payload(object|array|string $body): StreamInterface
    {
        return $this->client->createPayload($body);
    }
}
