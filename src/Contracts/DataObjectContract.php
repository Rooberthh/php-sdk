<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

interface DataObjectContract
{
    /**
     * @param class-string $class
     * @param array<mixed, mixed> $payload
     */
    public static function create(string $class, array $payload): DataObjectContract;

    /** @param array<mixed, mixed> $data */
    public static function make(array $data): DataObjectContract;
}
