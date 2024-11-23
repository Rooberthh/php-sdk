<?php

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientContract
{
    /**
     * Setup client.
     */
    public function setup(array $plugins): ClientContract;

    /**
     * Return the url
     */
    public function url(): string;

    /**
     * Send a request.
     */
    public function send(RequestInterface $request): ResponseInterface;
}
