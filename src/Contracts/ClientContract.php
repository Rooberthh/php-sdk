<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientContract
{
    public function defaultPlugins(): array;

    public function defaultHeaders(): array;

    public function url(): string;

    public function send(RequestInterface $request): ResponseInterface;

    public function setClient(ClientInterface $client): ClientContract;

    public function client(): PluginClient;
}
