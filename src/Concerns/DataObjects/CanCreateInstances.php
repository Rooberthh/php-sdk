<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\DataObjects;

use League\ObjectMapper\ObjectMapperUsingReflection;
use Rooberthh\Sdk\Contracts\DataObjectContract;

trait CanCreateInstances
{
    public static function create(string $class, array $payload): self
    {
        return (new ObjectMapperUsingReflection())->hydrateObject(
            className: $class,
            payload: $payload,
        );
    }

    public static function make(array $data): self
    {
        return self::create(__CLASS__, $data);
    }
}
