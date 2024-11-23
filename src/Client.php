<?php

namespace Rooberthh\Sdk;

use Http\Client\Common\PluginClient;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Exceptions\ClientSetupException;

abstract class Client implements ClientContract
{
    /**
     * The PSR-18 HTTP Client itself.
     */
    public ?ClientInterface $http = null;

    public function __construct(protected readonly string $url)
    {
        //
    }

    /**
     * Set up the client using PSR-18 discovery, passing in plugins.
     *
     * @see https://docs.php-http.org/en/latest/plugins/introduction.html#install
     *
     * @param  array<int,Plugin>  $plugins
     */
    public function setup(array $plugins = []): ClientContract
    {
        $this->http = new PluginClient(
            client: Psr18ClientDiscovery::find(),
            plugins: $plugins,
        );

        return $this;
    }

    /**
     * Return the URL for the API.
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Set the HTTP Client for the SDK.
     */
    public function client(ClientInterface $client): ClientContract
    {
        $this->http = $client;

        return $this;
    }

    /**
     * @throws ClientSetupException
     */
    public function send(RequestInterface $request): ResponseInterface
    {
        if (! $this->http instanceof ClientInterface) {
            throw new ClientSetupException('HTTP client is not an PSR-compatible client');
        }

        return $this->http->sendRequest($request);
    }
}
