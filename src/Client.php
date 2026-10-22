<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use CuyZ\Valinor\Normalizer\Configurator\NormalizeKeysToSnakeCase;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\NormalizerBuilder;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Psr\Http\Message\RequestInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\AuthenticationContract;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Http\Builder;
use Http\Client\Common\Plugin;

abstract class Client implements ClientContract
{
    private readonly Builder $builder;

    private bool $pluginsAdded = false;

    private ?Response $lastResponse = null;

    /**
     * Pass a builder to control the transport; omit it and one is assembled
     * from whatever PSR-18 client and PSR-17 factories are installed.
     *
     * The builder is copied, so the one passed in is never mutated and can be
     * reused across several clients. The client's own default plugins are added
     * to the copy on the first request, not here — so credentials and other
     * properties this client reads may be assigned anywhere in the constructor.
     *
     * @param string $url
     * @param ?Builder $builder
     */
    public function __construct(
        protected readonly string $url,
        ?Builder $builder = null,
    ) {
        $this->builder = clone ($builder ?? new Builder());
    }

    /**
     * Build a request to a path on this client's URL and send it.
     *
     * The body is encoded by type: an object is normalised to snake_case JSON
     * with null properties dropped, an array is JSON-encoded as given, a string
     * is sent raw, and null sends no body. Per-call headers override the
     * defaults, so a raw body can set its own `Content-Type`.
     *
     * @param Method                                 $method
     * @param string                                 $uri
     * @param array<string, mixed>                   $query   applied with {@see QueryBuilder}; null values are dropped
     * @param null|array<mixed, mixed>|object|string $body
     * @param array<string, string>                  $headers set on the request; these win over the defaults
     */
    public function request(
        Method $method,
        string $uri,
        array $query = [],
        object|array|string|null $body = null,
        array $headers = [],
    ): Response {
        $request = $this->builder()->getRequestFactory()->createRequest(
            $method->value,
            $this->url() . '/' . ltrim($uri, '/'),
        );

        $request = QueryBuilder::for($request)->whereAll($query)->apply();

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $request = $request->withBody(
                $this->builder()->getStreamFactory()->createStream($this->encodeBody($body)),
            );
        }

        return $this->send($request);
    }

    public function send(RequestInterface $request): Response
    {
        // Cleared first so a failed send leaves no earlier response reported as
        // though it were this one; the field is set again only on success.
        $this->lastResponse = null;

        return $this->lastResponse = new Response($this->builder()->getHttpClient()->sendRequest($request));
    }

    /**
     * The transport, for adding plugins after construction.
     *
     * The client's own default plugins (default headers, authentication and
     * {@see defaultPlugins()}) are added the first time this is called, after
     * any already on the injected builder. A consumer can still remove them
     * afterwards, since removal also goes through the builder this returns.
     */
    public function builder(): Builder
    {
        if (! $this->pluginsAdded) {
            $this->pluginsAdded = true;

            if ($this->defaultHeaders() !== []) {
                $this->builder->addPlugin(new HeaderDefaultsPlugin($this->defaultHeaders()));
            }

            if ($this instanceof AuthenticationContract) {
                $this->builder->addPlugin(new AuthenticationPlugin($this->authentication()));
            }

            foreach ($this->defaultPlugins() as $plugin) {
                $this->builder->addPlugin($plugin);
            }
        }

        return $this->builder;
    }

    /**
     * The last response received, or null if the most recent request failed
     * before one arrived. Useful after a call that returned data objects and
     * so discarded the response it came from.
     */
    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function url(): string
    {
        return rtrim($this->url, '/');
    }

    /**
     * Plugins applied to every request. Empty by default: errors are reported
     * by {@see Response::failed()} rather than thrown, and retrying is unsafe
     * on requests that are not idempotent — both are the SDK author's call.
     *
     * @return array<int, Plugin>
     */
    protected function defaultPlugins(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Encode a body for the wire, chosen by type: an object is normalised to
     * JSON, an array is JSON-encoded as given, and a string is sent raw.
     *
     * @param array<mixed, mixed>|object|string $body
     */
    private function encodeBody(object|array|string $body): string
    {
        return match (true) {
            is_string($body) => $body,
            is_array($body)  => json_encode($body, JSON_THROW_ON_ERROR),
            default          => json_encode($this->normalise($body), JSON_THROW_ON_ERROR),
        };
    }

    /**
     * Normalise a payload object to an array: camelCase properties become
     * snake_case keys, and null properties are dropped, so optional fields can
     * simply be left unset. The normaliser is built from reflection once and
     * reused, which is worth doing once rather than once per request.
     *
     * @param object $payload
     * @return array<mixed, mixed>
     */
    private function normalise(object $payload): array
    {
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

        return $normalizer->normalize($payload);
    }
}
