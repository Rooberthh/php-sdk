<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Http\Message\Authentication;
use Http\Message\Authentication\Bearer;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Tests\Stubs\Resources\TestResource;

class AuthenticatedSDK extends Client implements AuthenticationContract
{
    public function tests(): TestResource
    {
        return new TestResource($this);
    }

    public function authentication(): Authentication
    {
        return new Bearer('secret-token');
    }
}
