<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Response;

interface ClientContract
{
    public function url(): string;

    /**
     * @param Method               $method
     * @param string               $uri
     * @param array<string, mixed> $query
     */
    public function createRequest(Method $method, string $uri, array $query = []): RequestInterface;

    /**
     * @param array<mixed, mixed>|object|string $body
     */
    public function createPayload(object|array|string $body): StreamInterface;

    public function send(RequestInterface $request, ?Authentication $auth = null): Response;

    public function getLastResponse(): ?Response;
}
