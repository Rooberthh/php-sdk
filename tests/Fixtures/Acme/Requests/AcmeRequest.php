<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\RequestContract;

/**
 * What every Acme Request shares: the API wraps each result in a `data`
 * Envelope.
 *
 * @template T
 *
 * @implements RequestContract<T>
 */
abstract readonly class AcmeRequest implements RequestContract
{
    /**
     * What is inside the response's `data` Envelope.
     *
     * @param ResponseInterface $response
     *
     * @return array<mixed, mixed>
     */
    protected function data(ResponseInterface $response): array
    {
        /** @var array{data: array<mixed, mixed>} $decoded */
        $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded['data'];
    }
}
