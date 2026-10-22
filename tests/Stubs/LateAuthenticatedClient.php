<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Http\Message\Authentication;
use Http\Message\Authentication\Bearer;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Http\Builder;

/**
 * Assigns its token in the constructor body *after* parent::__construct().
 * This only works because the client assembles its plugins lazily, on the
 * first request, rather than inside the parent constructor.
 */
class LateAuthenticatedClient extends Client implements AuthenticationContract
{
    private string $token;

    public function __construct(string $url, string $token, ?Builder $builder = null)
    {
        parent::__construct($url, $builder);

        $this->token = $token;
    }

    public function authentication(): Authentication
    {
        return new Bearer($this->token);
    }
}
