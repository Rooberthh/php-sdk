<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a file to upload can't be sent as it is described.
 */
final class InvalidFile extends InvalidArgumentException
{
    public static function contentTypeHasLineBreak(): self
    {
        return new self("A file's Content-Type can't contain a line break.");
    }
}
