<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<list<Project>>
 */
final readonly class ListProjects extends AcmeRequest
{
    public function method(): Method
    {
        return Method::GET;
    }

    public function path(): string
    {
        return 'projects';
    }

    /**
     * @param ResponseInterface $response
     *
     * @return list<Project>
     */
    public function result(ResponseInterface $response): array
    {
        return array_values(array_map(Project::make(...), $this->data($response)));
    }
}
