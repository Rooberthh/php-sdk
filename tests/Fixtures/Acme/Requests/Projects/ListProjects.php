<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\HasQuery;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Enums\ProjectStatus;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<list<Project>>
 */
final readonly class ListProjects extends AcmeRequest implements HasQuery
{
    /**
     * @param ?ProjectStatus $status only projects with this status
     * @param ?int           $page   the API's default, the first, when null
     */
    public function __construct(
        public ?ProjectStatus $status = null,
        public ?int $page = null,
    ) {}

    public function method(): Method
    {
        return Method::GET;
    }

    public function path(): string
    {
        return 'projects';
    }

    public function query(): array
    {
        return [
            'status' => $this->status?->value,
            'page'   => $this->page,
        ];
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
