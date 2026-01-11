<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Filter;

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

    /** @param Filter[] $filters */
    public function applyFilters(RequestInterface $request, array $filters): RequestInterface
    {
        $parameters = [];

        foreach ($filters as $filter) {
            $parameters = array_merge($parameters, $filter->toQueryParameter());
        }

        $uri = $request->getUri()->withQuery(http_build_query($parameters));

        return $request->withUri(
            uri: $uri,
            preserveHost: true,
        );
    }

    public function payload(string $payload): StreamInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory()->createStream($payload);
    }
}
