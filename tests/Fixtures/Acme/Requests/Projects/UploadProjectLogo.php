<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Http\Method;
use Rooberthh\Sdk\Contracts\HasBody;
use Rooberthh\Sdk\Contracts\BodyContract;
use Rooberthh\Sdk\Http\Body\MultipartBody;
use Rooberthh\Sdk\Http\File;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\AcmeRequest;

/**
 * @extends AcmeRequest<Project>
 */
final readonly class UploadProjectLogo extends AcmeRequest implements HasBody
{
    /**
     * @param string  $id
     * @param File    $logo
     * @param ?string $alt text describing the logo, for screen readers
     */
    public function __construct(
        public string $id,
        public File $logo,
        public ?string $alt = null,
    ) {}

    public function method(): Method
    {
        return Method::POST;
    }

    public function path(): string
    {
        return 'projects/' . rawurlencode($this->id) . '/logo';
    }

    public function body(): BodyContract
    {
        return new MultipartBody([
            'logo' => $this->logo,
            'alt'  => $this->alt,
        ]);
    }

    /**
     * @param ResponseInterface $response
     */
    public function result(ResponseInterface $response): Project
    {
        return Project::make($this->data($response));
    }
}
