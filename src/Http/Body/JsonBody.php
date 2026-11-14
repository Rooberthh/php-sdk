<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http\Body;

use JsonException;
use JsonSerializable;
use Rooberthh\Sdk\Contracts\BodyContract;

/**
 * A Payload sent as JSON, as given: an object by its public properties, or by
 * its {@see JsonSerializable} implementation.
 */
final readonly class JsonBody implements BodyContract
{
    private string $content;

    /**
     * The Payload is encoded here, so one that can't be encoded fails where
     * the Request builds it, not later inside the Transport.
     *
     * @param array<mixed, mixed>|object $payload
     *
     * @throws JsonException when the Payload can't be encoded
     */
    public function __construct(array|object $payload)
    {
        $this->content = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}
