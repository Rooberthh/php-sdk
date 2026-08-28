<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\Resources;

use CuyZ\Valinor\Normalizer\Configurator\NormalizeKeysToSnakeCase;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\NormalizerBuilder;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;

trait CanCreateRequests
{
    public function request(Method $method, string $uri): RequestInterface
    {
        $uri = ltrim($uri, '/');

        return $this->client->builder()->getRequestFactory()->createRequest(
            method: $method->value,
            uri: "{$this->client->url()}/{$uri}",
        );
    }

    /**
     * Send a payload object as the request body: camelCase properties become
     * snake_case keys, and null properties are dropped, so optional fields can
     * simply be left unset.
     * @param object $payload
     */
    public function payload(object $payload): StreamInterface
    {
        // Held between calls: the normalizer is built from reflection, which is
        // worth doing once rather than once per request.
        static $normalizer = null;

        $normalizer ??= (new NormalizerBuilder())
            ->configureWith(new NormalizeKeysToSnakeCase())
            ->registerTransformer(
                static function (object $object, callable $next): mixed {
                    $normalised = $next();

                    return is_array($normalised)
                        ? array_filter($normalised, static fn(mixed $value): bool => $value !== null)
                        : $normalised;
                },
            )
            ->normalizer(Format::array());

        return $this->json($normalizer->normalize($payload));
    }

    /**
     * Send an array as the request body, encoded as given.
     *
     * @param array<mixed, mixed> $data
     */
    public function json(array $data): StreamInterface
    {
        return $this->body(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Send a string as the request body, untouched — for APIs that do not
     * speak JSON.
     * @param string $body
     */
    public function body(string $body): StreamInterface
    {
        return $this->client->builder()->getStreamFactory()->createStream($body);
    }
}
