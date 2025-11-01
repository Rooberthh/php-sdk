<?php

namespace Rooberthh\Sdk\Contracts;

use Http\Client\Common\Plugin\AuthenticationPlugin;

interface AuthenticationContract
{
    public function authentication(): AuthenticationPlugin;
}
