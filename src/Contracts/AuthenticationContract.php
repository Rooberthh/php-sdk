<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Client\Common\Plugin\AuthenticationPlugin;

interface AuthenticationContract
{
    public function authentication(): AuthenticationPlugin;
}
