<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use CuyZ\Valinor\Normalizer\Configurator\NormalizeKeysToSnakeCase;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\NormalizerBuilder;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\ClientContract;

/**
 * The entry point of an SDK: it builds requests against the API's URL and
 * sends them through the Transport.
 *
 * An SDK Author extends it and adds a method per Resource; the Consumer
 * constructs it with the URL and the Authentication strategy they hold, and
 * may add plugins ({@see self::setup()}) or swap the Transport
 * ({@see self::client()}) — both fluent, both allowed at any time.
 */
abstract class Client implements ClientContract
{
    private ClientInterface $http;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    /** @var list<Plugin> */
    private array $plugins = [];

    private ?ClientInterface $transport = null;

    private ?Response $lastResponse = null;

    /**
     * The PSR-18 client and PSR-17 factories are discovered from whatever is
     * installed, so nothing else needs wiring up.
     *
     * @param string               $url
     * @param null|Authentication  $auth applied to every request unless one overrides it ({@see self::send()})
     */
    public function __construct(
        protected readonly string $url,
        private readonly ?Authentication $auth = null,
    ) {
        $this->http = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Add plugins to the end of the chain, after the client's own.
     *
     * @param array<int, Plugin> $plugins
     */
    public function setup(array $plugins): static
    {
        array_push($this->plugins, ...array_values($plugins));
        $this->transport = null;

        return $this;
    }

    /**
     * Send through a different PSR-18 client — your own, or a
     * {@see Testing\FakeTransport} in tests. Plugins still apply.
     * @param ClientInterface $http
     */
    public function client(ClientInterface $http): static
    {
        $this->http = $http;
        $this->transport = null;

        return $this;
    }

    /**
     * A request to a path on this client's URL, ready to send.
     *
     * Null query values are dropped, so optional Filters can be passed straight
     * through; see {@see QueryBuilder} for how the rest are written.
     *
     * @param Method               $method
     * @param string               $uri
     * @param array<string, mixed> $query
     */
    public function createRequest(Method $method, string $uri, array $query = []): RequestInterface
    {
        $request = $this->requestFactory->createRequest(
            $method->value,
            $this->url() . '/' . ltrim($uri, '/'),
        );

        return $query === [] ? $request : QueryBuilder::for($request)->whereAll($query)->apply();
    }

    /**
     * A request body, encoded by type: an object is normalised to snake_case
     * JSON with null properties dropped, an array is JSON-encoded as given, and
     * a string is sent raw.
     *
     * @param array<mixed, mixed>|object|string $body
     */
    public function createPayload(object|array|string $body): StreamInterface
    {
        return $this->streamFactory->createStream(match (true) {
            is_string($body) => $body,
            is_array($body)  => json_encode($body, JSON_THROW_ON_ERROR),
            default          => json_encode($this->normalise($body), JSON_THROW_ON_ERROR),
        });
    }

    /**
     * Send a request through the Transport.
     *
     * Authentication is applied here, before the plugin chain, so every plugin
     * and the Transport see the request as it goes over the wire. Pass `$auth`
     * to use other credentials for this one request, or {@see Auth\NoAuth} to
     * send it without any.
     * @param RequestInterface $request
     * @param ?Authentication $auth
     */
    public function send(RequestInterface $request, ?Authentication $auth = null): Response
    {
        // Cleared first so a failed send leaves no earlier response reported as
        // though it were this one; the field is set again only on success.
        $this->lastResponse = null;

        $auth ??= $this->auth;

        if ($auth !== null) {
            $request = $auth->authenticate($request);
        }

        return $this->lastResponse = new Response($this->transport()->sendRequest($request));
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
     * The PSR-18 client with every plugin applied: default headers, then
     * {@see self::defaultPlugins()}, then those added through {@see self::setup()}.
     *
     * Composed on first use rather than in the constructor, so the defaults may
     * read properties a subclass assigns after calling parent::__construct(),
     * and composed again after the plugins or the client change.
     */
    private function transport(): ClientInterface
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        $plugins = $this->defaultHeaders() === [] ? [] : [new HeaderDefaultsPlugin($this->defaultHeaders())];

        return $this->transport = (new PluginClientFactory())->createClient(
            $this->http,
            [...$plugins, ...array_values($this->defaultPlugins()), ...$this->plugins],
        );
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
