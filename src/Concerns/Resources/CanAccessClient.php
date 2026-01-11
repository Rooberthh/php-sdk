<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use Rooberthh\Sdk\Contracts\ClientContract;

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
