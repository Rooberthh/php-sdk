<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects;

use DateTimeImmutable;
use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Enums\ProjectStatus;

final readonly class Project implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ProjectStatus $status,
        public DateTimeImmutable $createdAt,
    ) {}
}
