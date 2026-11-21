<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Http\Method;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<Project>
 */
final readonly class GetProject extends AcmeRequest
{
    public function __construct(
        public string $id,
    ) {}

    public function method(): Method
    {
        return Method::GET;
    }

    public function path(): string
    {
        return 'projects/' . rawurlencode($this->id);
    }

    /**
     * @param ResponseInterface $response
     */
    public function result(ResponseInterface $response): Project
    {
        return Project::make($this->data($response));
    }
}
