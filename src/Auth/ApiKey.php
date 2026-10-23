<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Auth;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;
use SensitiveParameter;

/**
 * Sends an API key in a header or a query parameter, under whatever name the
 * API expects.
 */
final readonly class ApiKey implements Authentication
{
    private function __construct(
        private string $name,
        #[SensitiveParameter]
        private string $key,
        private bool $inQuery,
    ) {}

    /**
     * `ApiKey::header('X-Api-Key', $key)`
     * @param string $name
     * @param string $key
     */
    public static function header(string $name, #[SensitiveParameter] string $key): self
    {
        return new self($name, $key, inQuery: false);
    }

    /**
     * `ApiKey::query('api_key', $key)`
     * @param string $name
     * @param string $key
     */
    public static function query(string $name, #[SensitiveParameter] string $key): self
    {
        return new self($name, $key, inQuery: true);
    }

    public function authenticate(RequestInterface $request): RequestInterface
    {
        if (!$this->inQuery) {
            return $request->withHeader($this->name, $this->key);
        }

        // Appended rather than parsed and rebuilt, so the query the request
        // already carries goes out exactly as it was written.
        $uri = $request->getUri();
        $param = rawurlencode($this->name) . '=' . rawurlencode($this->key);

        return $request->withUri(
            $uri->withQuery($uri->getQuery() === '' ? $param : $uri->getQuery() . '&' . $param),
        );
    }
}
