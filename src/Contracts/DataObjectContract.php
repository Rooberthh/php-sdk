<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

interface DataObjectContract
{
    /**
     * Build the object from a decoded API response.
     *
     * @param array<mixed, mixed> $data
     */
    public static function make(array $data): static;
}
