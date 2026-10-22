<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\Stubs\Exceptions\NotFound;
use Rooberthh\Sdk\Tests\Stubs\Resources\TestResource;
use Throwable;
use Rooberthh\Sdk\Exceptions\RequestFailed;

/**
 * A client that maps a 404 to its own {@see NotFound} and leaves everything
 * else to {@see RequestFailed}. Counts how often the hook is consulted so a
 * test can prove it is not called for a 2xx response.
 */
class ErrorMappingClient extends Client
{
    public int $calls = 0;

    public function tests(): TestResource
    {
        return new TestResource($this);
    }

    protected function exceptionFor(Response $response): ?Throwable
    {
        $this->calls++;

        return match ($response->status()) {
            404     => new NotFound($response),
            default => null,
        };
    }
}
