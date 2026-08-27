<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Payloads;

use Rooberthh\Sdk\Tests\Stubs\Enums\Status;

readonly class CreateTestPayload
{
    /**
     * @param list<CreateTestLinePayload> $orderLines
     * @param null|list<string>           $tags
     * @param string $purchaseCountry
     * @param ?Status $status
     * @param ?string $merchantReference1
     */
    public function __construct(
        public string $purchaseCountry,
        public array $orderLines = [],
        public ?Status $status = null,
        public ?string $merchantReference1 = null,
        public ?array $tags = null,
    ) {}
}
