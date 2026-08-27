<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use RuntimeException;
use Rooberthh\Sdk\Response;

/**
 * Thrown by {@see \Rooberthh\Sdk\Response::throw()} when a request did not
 * come back with a 2xx status. Never thrown unless asked for.
 */
final class RequestFailed extends RuntimeException implements SdkException
{
    private function __construct(
        string $message,
        public readonly Response $response,
    ) {
        parent::__construct($message);
    }

    public static function with(Response $response): self
    {
        return new self(
            sprintf('The API responded with %d: %s', $response->status(), $response->body()),
            $response,
        );
    }
}
