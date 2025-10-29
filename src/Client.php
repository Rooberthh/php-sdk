<?php

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Exceptions\SetupException;

abstract class Client implements ClientContract
{
    public ?ClientInterface $http = null;

    public function __construct(
        protected readonly string $url,
        protected ClientInterface $client,
        protected array $plugins = [],
    ) {
        //
    }

    /**
     * @throws SetupException
     * @param RequestInterface $request
     */
    public function send(RequestInterface $request): ResponseInterface
    {
        if (! $this->client instanceof ClientInterface) {
            throw new SetupException('HTTP client is not an PSR-compatible client');
        }

        return $this->client->sendRequest($request);
    }

    public function client(): PluginClient
    {
        return new PluginClient(
            client: Psr18ClientDiscovery::find(),
            plugins: $this->plugins($this->plugins),
        );
    }

    public function url(): string
    {
        return rtrim($this->url, '/');
    }

    public function plugins(array $plugins = []): array
    {
        $this->plugins = array_merge($this->defaultPlugins(), $plugins);

        return $this->plugins;
    }

    public function defaultPlugins(): array
    {
        return [
            new RetryPlugin(),
            new ErrorPlugin(),
        ];
    }

    public function setClient(ClientInterface $client): ClientContract
    {
        $this->client = $client;

        return $this;
    }
}
