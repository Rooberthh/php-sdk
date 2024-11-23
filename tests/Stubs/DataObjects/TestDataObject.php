<?php

namespace Rooberthh\Sdk\Tests\Stubs\DataObjects;

use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;

readonly class TestDataObject implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public int $id,
        public string $name,
        public string $desc,
    ) {
        //
    }
}
