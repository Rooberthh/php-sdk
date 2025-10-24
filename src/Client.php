<?php

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Exceptions\SetupException;

abstract class Client implements ClientContract
{
    /**
     * The PSR-18 HTTP Client itself.
     */
    public ?ClientInterface $http = null;

    public function __construct(protected readonly string $url, protected array $plugins = [])
    {
        //
    }

    public function withPlugins(array $plugins): ClientContract
    {
        $this->plugins = array_merge($this->defaultPlugins(), $plugins);
    }

    public function defaultPlugins(): array
    {
        return [
            new RetryPlugin(),
            new ErrorPlugin(),
        ];
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
     * @param ClientInterface $client
     * @return ClientContract
     */
    public function client(ClientInterface $client): ClientContract
    {
        $this->http = $client;

        return $this;
    }

    /**
     * @param RequestInterface $request
     * @throws SetupException
     */
    public function send(RequestInterface $request): ResponseInterface
    {
        if (! $this->http instanceof ClientInterface) {
            throw new SetupException('HTTP client is not an PSR-compatible client');
        }

        return $this->http->sendRequest($request);
    }
}
