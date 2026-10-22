<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Message\RequestInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Response;

interface ClientContract
{
    /**
     * @param Method                                 $method
     * @param string                                 $uri
     * @param array<string, mixed>                   $query
     * @param null|array<mixed, mixed>|object|string $body
     * @param array<string, string>                  $headers
     */
    public function request(
        Method $method,
        string $uri,
        array $query = [],
        object|array|string|null $body = null,
        array $headers = [],
    ): Response;

    public function send(RequestInterface $request): Response;

    public function getLastResponse(): ?Response;
}
