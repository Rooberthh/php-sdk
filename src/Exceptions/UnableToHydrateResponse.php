<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use CuyZ\Valinor\Mapper\MappingError;
use RuntimeException;

/**
 * Thrown when an API response does not match the object it maps onto —
 * usually because the upstream API changed shape.
 */
final class UnableToHydrateResponse extends RuntimeException implements SdkException
{
    /**
     * @param array<int, string> $errors
     * @param string $message
     * @param string $class
     * @param ?MappingError $previous
     */
    private function __construct(
        string $message,
        public readonly string $class,
        public readonly array $errors,
        ?MappingError $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param class-string $class
     * @param MappingError $error
     */
    public static function for(string $class, MappingError $error): self
    {
        $errors = [];

        foreach ($error->messages()->errors() as $message) {
            $errors[] = "{$message->path()}: {$message->toString()}";
        }

        return new self(
            sprintf(
                "Could not hydrate [%s] from the API response:\n  - %s",
                $class,
                implode("\n  - ", $errors),
            ),
            $class,
            $errors,
            $error,
        );
    }
}
