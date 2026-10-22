<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Http\Builder;
use Http\Client\Common\Plugin;

abstract class Client implements ClientContract
{
    private readonly Builder $builder;

    private ?Response $lastResponse = null;

    /**
     * Pass a builder to control the transport; omit it and one is assembled
     * from whatever PSR-18 client and PSR-17 factories are installed.
     * @param string $url
     * @param ?Builder $builder
     */
    public function __construct(
        protected readonly string $url,
        ?Builder $builder = null,
    ) {
        $this->builder = $builder ?? new Builder();

        if ($this->defaultHeaders() !== []) {
            $this->builder->addPlugin(new HeaderDefaultsPlugin($this->defaultHeaders()));
        }

        if ($this instanceof AuthenticationContract) {
            $this->builder->addPlugin(new AuthenticationPlugin($this->authentication()));
        }

        foreach ($this->defaultPlugins() as $plugin) {
            $this->builder->addPlugin($plugin);
        }
    }

    public function send(RequestInterface $request): Response
    {
        // Cleared first so a failed send leaves no earlier response reported as
        // though it were this one; the field is set again only on success.
        $this->lastResponse = null;

        return $this->lastResponse = new Response($this->builder->getHttpClient()->sendRequest($request));
    }

    /**
     * The transport, for adding plugins after construction.
     */
    public function builder(): Builder
    {
        return $this->builder;
    }

    /**
     * The last response received, or null if the most recent request failed
     * before one arrived. Useful after a call that returned data objects and
     * so discarded the response it came from.
     */
    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function url(): string
    {
        return rtrim($this->url, '/');
    }

    /**
     * Plugins applied to every request. Empty by default: errors are reported
     * by {@see Response::failed()} rather than thrown, and retrying is unsafe
     * on requests that are not idempotent — both are the SDK author's call.
     *
     * @return array<int, Plugin>
     */
    public function defaultPlugins(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
