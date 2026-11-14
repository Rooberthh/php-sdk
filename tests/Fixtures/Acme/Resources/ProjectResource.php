<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Resources;

use Rooberthh\Sdk\Http\File;
use Rooberthh\Sdk\Tests\Fixtures\Acme\AcmeConnector;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Payloads\CreateProjectPayload;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\CreateProject;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\DeleteProject;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\GetProject;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\ListProjects;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\UploadProjectLogo;

/**
 * The operations on Acme projects, as methods. Each one only creates its
 * Request and sends it, so a Consumer can send the Requests directly instead.
 */
final readonly class ProjectResource
{
    public function __construct(
        private AcmeConnector $acme,
    ) {}

    /**
     * @return list<Project>
     */
    public function list(): array
    {
        return $this->acme->send(new ListProjects());
    }

    /**
     * @param string $id
     */
    public function get(string $id): Project
    {
        return $this->acme->send(new GetProject($id));
    }

    /**
     * @param CreateProjectPayload $payload
     */
    public function create(CreateProjectPayload $payload): Project
    {
        return $this->acme->send(new CreateProject($payload));
    }

    /**
     * @param string  $id
     * @param File    $logo
     * @param ?string $alt text describing the logo, for screen readers
     */
    public function uploadLogo(string $id, File $logo, ?string $alt = null): Project
    {
        return $this->acme->send(new UploadProjectLogo($id, $logo, $alt));
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        $this->acme->send(new DeleteProject($id));
    }
}
