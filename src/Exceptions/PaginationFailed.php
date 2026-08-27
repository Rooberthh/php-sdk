<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use RuntimeException;

final class PaginationFailed extends RuntimeException implements SdkException
{
    public static function tooManyPages(int $maxPages): self
    {
        return new self(sprintf(
            'Stopped after %d pages. The endpoint kept returning full pages — raise maxPages if that is expected.',
            $maxPages,
        ));
    }
}
