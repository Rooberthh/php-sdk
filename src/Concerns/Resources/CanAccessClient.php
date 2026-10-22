<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use Rooberthh\Sdk\Contracts\ClientContract;

/**
 * Gives a Resource the Client it makes requests through.
 *
 * It exists for SDK Authors to compose into their own Resources, so nothing
 * inside src/ uses it.
 *
 * @phpstan-ignore trait.unused
 */
trait CanAccessClient
{
    /**
     * @param ClientContract $client
     */
    public function __construct(protected readonly ClientContract $client)
    {
        //
    }
}
