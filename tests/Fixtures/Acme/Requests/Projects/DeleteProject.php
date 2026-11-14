<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<null>
 */
final readonly class DeleteProject extends AcmeRequest
{
    public function __construct(
        public string $id,
    ) {}

    public function method(): Method
    {
        return Method::DELETE;
    }

    public function path(): string
    {
        return 'projects/' . rawurlencode($this->id);
    }

    /**
     * The API answers 204 with no body.
     *
     * @param ResponseInterface $response
     */
    public function result(ResponseInterface $response): null
    {
        return null;
    }
}
