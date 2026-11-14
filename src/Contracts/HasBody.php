<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Rooberthh\Sdk\Http\Body\JsonBody;

/**
 * A Request that sends a Payload. Requests without one don't implement it,
 * and go out with no body and no Content-Type.
 */
interface HasBody
{
    /**
     * The Payload, encoded the way the API expects, e.g.
     * `new JsonBody($this->payload)` ({@see JsonBody}).
     */
    public function body(): BodyContract;
}
