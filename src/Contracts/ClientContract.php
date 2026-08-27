<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Response;

interface ClientContract
{
    /** @return array<int, Plugin> */
    public function defaultPlugins(): array;

    /** @return array<string, string> */
    public function defaultHeaders(): array;

    public function url(): string;

    public function send(RequestInterface $request): Response;

    public function setClient(ClientInterface $client): ClientContract;

    public function client(): PluginClient;
}
