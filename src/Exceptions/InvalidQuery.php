<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a Request's Query can't be encoded as it is described.
 */
final class InvalidQuery extends InvalidArgumentException
{
    public static function notAList(string $name): self
    {
        return new self("The query parameter [{$name}] must be a value or a list of values; name nested keys in full, e.g. [{$name}[key]].");
    }
}
