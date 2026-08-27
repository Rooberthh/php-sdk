<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Payloads;

readonly class CreateTestLinePayload
{
    public function __construct(
        public string $name,
        public int $unitPrice,
        public ?string $productUrl = null,
    ) {}
}
