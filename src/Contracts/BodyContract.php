<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Message\StreamInterface;

/**
 * A Payload encoded for sending, together with the Content-Type that says how.
 *
 * Each encoding is its own class (`Http\Body\JsonBody`, `FormBody`,
 * `MultipartBody`, `RawBody`), so the content and its Content-Type can't
 * disagree. An SDK Author whose API wants another encoding implements this.
 */
interface BodyContract
{
    /**
     * The encoded Payload: a string, or a stream when copying it into a
     * string would be wasteful, as with file uploads.
     */
    public function content(): StreamInterface|string;

    /**
     * Sent as the request's Content-Type. No default or Consumer header
     * replaces it.
     */
    public function contentType(): string;
}
