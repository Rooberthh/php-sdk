<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Auth;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * Sends `Authorization: Bearer <token>`.
 */
final readonly class BearerToken implements Authentication
{
    public function __construct(
        #[SensitiveParameter]
        private string $token,
    ) {}

    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }
}
