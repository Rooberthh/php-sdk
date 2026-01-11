<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

interface DataObjectContract
{
    public static function create(string $class, array $payload): DataObjectContract;

    public static function make(array $data): DataObjectContract;
}
