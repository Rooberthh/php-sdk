<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Message\Authentication;

/**
 * Declares that a client authenticates its requests.
 *
 * Implement this and the credentials are applied to everything the client
 * sends — the client wires it up in its constructor, so there is no separate
 * setup step.
 *
 * `php-http/message` ships the common forms — `Bearer`, `BasicAuth`,
 * `QueryParam`, `Header`, `Wsse`, and `Chain` for combining them. Anything
 * else is your own `Authentication`: it is handed the request and returns it
 * signed, so request signing and fetching a fresh token both belong there.
 */
interface AuthenticationContract
{
    public function authentication(): Authentication;
}
