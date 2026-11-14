<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Resources;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Tests\Fixtures\Acme\AcmeConnector;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Enums\ProjectStatus;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Payloads\CreateProjectPayload;

/**
 * The operations on Acme projects. The API wraps every result in a `data`
 * Envelope.
 */
final readonly class ProjectResource
{
    public function __construct(
        private AcmeConnector $acme,
    ) {}

    /**
     * @param ?ProjectStatus $status only projects with this status; all of them when null
     *
     * @return list<Project>
     */
    public function list(?ProjectStatus $status = null): array
    {
        $request = $this->acme->createRequest(Method::GET, 'projects', [
            'status' => $status?->value,
        ]);

        return array_map(
            Project::make(...),
            $this->data($this->acme->send($request)),
        );
    }

    /**
     * @param string $id
     */
    public function get(string $id): Project
    {
        $request = $this->acme->createRequest(Method::GET, 'projects/' . rawurlencode($id));

        return Project::make($this->data($this->acme->send($request)));
    }

    /**
     * @param CreateProjectPayload $payload
     */
    public function create(CreateProjectPayload $payload): Project
    {
        $request = $this->acme->createRequest(Method::POST, 'projects')
            ->withBody($this->acme->createPayload($payload));

        return Project::make($this->data($this->acme->send($request)));
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        $this->acme->send(
            $this->acme->createRequest(Method::DELETE, 'projects/' . rawurlencode($id)),
        );
    }

    /**
     * What is inside the response's `data` Envelope.
     *
     * @param ResponseInterface $response
     *
     * @return array<mixed, mixed>
     */
    private function data(ResponseInterface $response): array
    {
        /** @var array{data: array<mixed, mixed>} $decoded */
        $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded['data'];
    }
}
