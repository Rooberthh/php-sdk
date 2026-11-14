<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\HasBody;
use Rooberthh\Sdk\Contracts\BodyContract;
use Rooberthh\Sdk\Http\Body\JsonBody;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Payloads\CreateProjectPayload;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<Project>
 */
final readonly class CreateProject extends AcmeRequest implements HasBody
{
    public function __construct(
        public CreateProjectPayload $payload,
    ) {}

    public function method(): Method
    {
        return Method::POST;
    }

    public function path(): string
    {
        return 'projects';
    }

    public function body(): BodyContract
    {
        return new JsonBody($this->payload);
    }

    /**
     * @param ResponseInterface $response
     */
    public function result(ResponseInterface $response): Project
    {
        return Project::make($this->data($response));
    }
}
