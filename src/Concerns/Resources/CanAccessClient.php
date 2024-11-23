<?php

namespace Rooberthh\Sdk\Concerns\Resources;

use Crell\Serde\SerdeCommon;
use Rooberthh\Sdk\Contracts\ClientContract;

trait CanAccessClient
{
    /**
     * @param ClientContract $client
     * @param SerdeCommon    $serialize
     * @param SerdeCommon $serializer
     */
    public function __construct(
        protected readonly ClientContract $client,
        protected readonly SerdeCommon $serializer = new SerdeCommon(),
    ) {
        //
    }
}
