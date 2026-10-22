<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Testing;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds PSR-7 responses for a {@see FakeTransport} to hand back.
 *
 * Only the two shapes a test actually reaches for: a JSON body, and a bare
 * status with an optional raw body. Named constructors rather than `new` so a
 * test reads as the response it describes.
 */
final class FakeResponse
{
    private function __construct() {}

    /**
     * A JSON response. The body is encoded and `Content-Type: application/json`
     * is set unless `$headers` already names a Content-Type of its own.
     *
     * @param array<mixed, mixed>   $body
     * @param int                   $status
     * @param array<string, string> $headers
     *
     * @throws \JsonException
     */
    public static function json(array $body, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * A response with just a status and, optionally, a raw body.
     *
     * @param int                   $status
     * @param string                $body
     * @param array<string, string> $headers
     */
    public static function status(int $status, string $body = '', array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $body);
    }
}
