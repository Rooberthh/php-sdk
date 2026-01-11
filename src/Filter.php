<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

readonly class Filter
{
    public function __construct(
        protected string $key,
        protected string $value,
    ) {
        //
    }

    /** @return array{string, mixed} */
    public function toQueryParameter(): array
    {
        return [
            $this->key => $this->value,
        ];
    }

    public static function make(string $key, mixed $value): Filter
    {
        return new Filter($key, $value);
    }
}
