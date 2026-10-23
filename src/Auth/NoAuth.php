<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Auth;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

/**
 * Sends the request as it is. Pass it to `send()` for the one endpoint that
 * must go out without the Client's credentials — a public status page, a
 * login call.
 */
final readonly class NoAuth implements Authentication
{
    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
