<?php

namespace Rooberthh\Sdk\Concerns\Resources;

use Rooberthh\Sdk\Contracts\DataObjectContract;

trait CanCreateDataObjects
{
    public function make(string $class, string $body): DataObjectContract
    {
        return $this->serializer->deserialize(
            serialized: $body,
            from: 'json',
            to: $class,
        );
    }
}
