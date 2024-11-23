<?php

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientContract
{
    /**
     * Setup client.
     * @param array $plugins
     */
    public function setup(array $plugins): ClientContract;

    /**
     * Return the url
     */
    public function url(): string;

    /**
     * Send a request.
     * @param RequestInterface $request
     */
    public function send(RequestInterface $request): ResponseInterface;
}
