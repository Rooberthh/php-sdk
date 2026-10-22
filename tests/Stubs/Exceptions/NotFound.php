<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Exceptions;

use RuntimeException;
use Rooberthh\Sdk\Exceptions\SdkException;
use Rooberthh\Sdk\Response;

/**
 * A stub exception an SDK maps a 404 to, carrying the response it came from.
 */
final class NotFound extends RuntimeException implements SdkException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('Not found');
    }
}
