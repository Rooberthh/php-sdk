<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

/**
 * The HTTP method a Request is sent with; the Connector sends its value as is.
 */
enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';
}
