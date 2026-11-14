<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http\Body;

use Rooberthh\Sdk\Contracts\BodyContract;

/**
 * Content the Request has encoded itself, such as XML or CSV, sent as it is.
 */
final readonly class RawBody implements BodyContract
{
    /**
     * @param string $content
     * @param string $contentType
     */
    public function __construct(
        private string $content,
        private string $contentType,
    ) {}

    public function content(): string
    {
        return $this->content;
    }

    public function contentType(): string
    {
        return $this->contentType;
    }
}
