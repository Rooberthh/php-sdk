<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\DataObjects;

use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;

readonly class NestedDataObject implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public int $id,
        public string $name,
        public string $desc,
        public PriceObject $cardPrices,
    ) {
        //
    }
}
