<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Tests\Stubs\Resources\TestResource;

class MockSDK extends Client
{
    public function tests(): TestResource
    {
        return new TestResource($this);
    }
}
