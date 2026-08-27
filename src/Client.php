<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Contracts\ClientContract;

abstract class Client implements ClientContract
{
    public ?ClientInterface $http = null;

    public function __construct(
        protected readonly string $url,
        protected ClientInterface $client,
    ) {
        //
    }

    public function send(RequestInterface $request): Response
    {
        return new Response($this->client->sendRequest($request));
    }

    public function client(): PluginClient
    {
        return new PluginClient(
            client: Psr18ClientDiscovery::find(),
            plugins: $this->defaultPlugins(),
        );
    }

    public function url(): string
    {
        return rtrim($this->url, '/');
    }

    public function defaultPlugins(): array
    {
        $defaultPlugins = [
            new ErrorPlugin(),
            new RetryPlugin(),
        ];

        if ($this instanceof AuthenticationContract) {
            $defaultPlugins[] = $this->authentication();
        }

        if (!empty($this->defaultHeaders())) {
            $defaultPlugins[] = new HeaderAppendPlugin($this->defaultHeaders());
        }

        return $defaultPlugins;
    }

    public function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function setClient(ClientInterface $client): ClientContract
    {
        $this->client = $client;

        return $this;
    }
}
