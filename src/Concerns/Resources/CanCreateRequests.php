<?php

namespace Rooberthh\Sdk\Concerns\Resources;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

trait CanCreateRequests
{
    public function request($method, string $uri): RequestInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest(
            method: $method,
            uri: "{$this->client->url()}{$uri}",
        );
    }

    public function payload(string $payload): StreamInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory()->createStream(
            content: $payload,
        );
    }
}
