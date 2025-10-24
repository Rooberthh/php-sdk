<?php

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientContract
{
    public function withPlugins(array $plugins): ClientContract;

    public function defaultPlugins(): array;

    public function url(): string;

    public function send(RequestInterface $request): ResponseInterface;

    public function client(ClientInterface $client): ClientContract;
}
