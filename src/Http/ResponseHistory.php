<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Http\Client\Common\Plugin\Journal;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Remembers the most recent exchange.
 *
 * Resource methods usually return data objects, which leaves no way back to
 * the status, headers, or request that produced them. This keeps the last one
 * reachable — for pagination links, rate limit headers and request ids.
 */
final class ResponseHistory implements Journal
{
    private ?RequestInterface $request = null;

    private ?ResponseInterface $response = null;

    public function addSuccess(RequestInterface $request, ResponseInterface $response): void
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function addFailure(RequestInterface $request, ClientExceptionInterface $exception): void
    {
        $this->request = $request;
        // A failed call has no response; clearing it stops an earlier success
        // from being reported as though it were this one.
        $this->response = null;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function lastResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
