<?php

namespace Rooberthh\Sdk\Tests\Stubs\DataObjects;

use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;

readonly class PriceObject implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public string $cardmarket_price,
        public string $tcgplayer_price,
        public string $amazon_price,
    ) {
        //
    }
}
