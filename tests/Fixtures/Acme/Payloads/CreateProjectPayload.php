<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Payloads;

final readonly class CreateProjectPayload
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
