<?php

namespace Rooberthh\Sdk\Concerns\DataObjects;

use League\ObjectMapper\ObjectMapperUsingReflection;
use Rooberthh\Sdk\Contracts\DataObjectContract;

trait CanCreateInstances
{
    public static function create(string $class, array $payload): DataObjectContract
    {
        return (new ObjectMapperUsingReflection)->hydrateObject(
            className: $class,
            payload: $payload,
        );
    }

    public static function make(array $data): DataObjectContract
    {
        return (new ObjectMapperUsingReflection)->hydrateObject(
            className: __CLASS__,
            payload: $data,
        );
    }
}
