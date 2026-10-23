<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Auth;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * Sends `Authorization: Basic <base64 of username:password>`.
 */
final readonly class BasicAuth implements Authentication
{
    public function __construct(
        private string $username,
        #[SensitiveParameter]
        private string $password,
    ) {}

    public function authenticate(RequestInterface $request): RequestInterface
    {
        return $request->withHeader(
            'Authorization',
            'Basic ' . base64_encode($this->username . ':' . $this->password),
        );
    }
}
