<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;

trait CanCreateRequests
{
    public function request(Method $method, string $uri): RequestInterface
    {
        $uri = ltrim($uri, '/');

        return Psr17FactoryDiscovery::findRequestFactory()->createRequest(
            method: $method->value,
            uri: "{$this->client->url()}/{$uri}",
        );
    }

    public function payload(string $payload): StreamInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory()->createStream($payload);
    }
}
