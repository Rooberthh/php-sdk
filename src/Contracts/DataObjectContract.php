<?php

namespace Rooberthh\Sdk\Contracts;

interface DataObjectContract
{
    /**
     * @param  string  $class
     * @param  array   $payload
     * @return DataObjectContract
     */
    public static function create(string $class, array $payload): DataObjectContract;

    public static function make(array $data): DataObjectContract;
}
