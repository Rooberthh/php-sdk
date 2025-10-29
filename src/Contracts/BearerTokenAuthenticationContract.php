<?php

namespace Rooberthh\Sdk\Contracts;

interface BearerTokenAuthenticationContract
{
    public function token(): string;
}
