<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Exceptions;

use Throwable;

/**
 * Every exception this package throws implements this interface, so consumers
 * can catch anything the SDK raises without catching transport exceptions too.
 */
interface SdkException extends Throwable {}
