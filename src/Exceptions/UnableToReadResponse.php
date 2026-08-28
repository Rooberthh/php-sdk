<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use RuntimeException;
use Rooberthh\Sdk\Response;
use Throwable;

/**
 * Thrown when a response body cannot be read as expected — usually an error
 * page or a gateway response arriving where JSON was expected.
 */
final class UnableToReadResponse extends RuntimeException implements SdkException
{
    private function __construct(
        string $message,
        public readonly Response $response,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function missingKey(Response $response, string|int $key): self
    {
        return new self(
            sprintf(
                'The API response has no [%s] to read. It contains: %s.',
                $key,
                implode(', ', array_map(strval(...), array_keys($response->json()))) ?: '(nothing)',
            ),
            $response,
        );
    }

    public static function notJson(Response $response, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Expected a JSON body but the API responded with %d: %s',
                $response->status(),
                $response->body(),
            ),
            $response,
            $previous,
        );
    }
}
