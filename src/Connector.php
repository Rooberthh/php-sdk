<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Http\BaseUri;

class Connector
{
    public const string VERSION = '0.1.0';

    private readonly BaseUri $baseUri;

    private ClientInterface $http;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    /**
     * Sent with every request unless the request sets its own: the
     * {@see self::defaultHeaders()}, with the Consumer's headers over them.
     *
     * @var array<string, string>
     */
    private readonly array $headers;

    /** @var list<Plugin> */
    private array $plugins = [];

    /**
     * The PSR-18 client and PSR-17 factories are discovered from whatever is
     * installed, so nothing else needs wiring up.
     *
     * @param string               $url  the API's base URI; every request path is resolved under it ({@see BaseUri})
     * @param null|Authentication  $auth applied to every request unless one overrides it ({@see self::send()})
     * @param array<string, string> $headers sent with every request, replacing a default header of the same name
     * @param ?ClientInterface $http
     *
     * @throws Exceptions\UnsafeUri when `$url` is not a safe base URI
     */
    public function __construct(
        string $url,
        private readonly ?Authentication $auth = null,
        array $headers = [],
        ?ClientInterface $http = null,
    ) {
        $this->baseUri = new BaseUri($url);
        $this->headers = [...$this->defaultHeaders(), ...$headers];
        $this->http = $http ?? Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Add plugins to the end of the chain, after the headers.
     *
     * @param array<int, Plugin> $plugins
     */
    public function setup(array $plugins): static
    {
        array_push($this->plugins, ...array_values($plugins));

        return $this;
    }

    /**
     * Send through a different PSR-18 client. Plugins still apply.
     * @param ClientInterface $http
     */
    public function client(ClientInterface $http): static
    {
        $this->http = $http;

        return $this;
    }

    /**
     * A request to a path under this client's base URI, ready to send.
     *
     * The path is resolved by {@see BaseUri::resolve()}, so it can't carry a
     * query or leave the base. Query parameters go in `$query`: null values are
     * dropped, so optional Filters can be passed straight through, and the rest
     * are encoded per RFC 3986.
     *
     * @param Method               $method
     * @param string               $path
     * @param array<string, mixed> $query
     *
     * @throws Exceptions\UnsafeUri when the path would leave the base URI
     */
    public function createRequest(Method $method, string $path, array $query = []): RequestInterface
    {
        $uri = $this->baseUri->resolve($path);

        $query = array_filter($query, static fn(mixed $value): bool => $value !== null);

        if ($query !== []) {
            $uri = $uri->withQuery(http_build_query($query, encoding_type: PHP_QUERY_RFC3986));
        }

        return $this->requestFactory->createRequest($method->value, $uri->toString());
    }

    /**
     * A request body, encoded by type: a string is sent raw, and an array or
     * object is JSON-encoded as given (an object by its public properties, or
     * by its {@see \JsonSerializable} implementation).
     *
     * @param array<mixed, mixed>|object|string $body
     */
    public function createPayload(object|array|string $body): StreamInterface
    {
        return $this->streamFactory->createStream(match (true) {
            is_string($body) => $body,
            default          => json_encode($body, JSON_THROW_ON_ERROR),
        });
    }

    /**
     * Send a request through the Transport.
     *
     * Authentication is applied here, before the plugin chain, so every plugin
     * and the Transport see the request as it goes over the wire. The chain is
     * the headers, then the plugins added through {@see self::setup()}. Pass `$auth` to use other credentials for this one
     * request, or {@see Auth\NoAuth} to send it without any.
     * @param RequestInterface $request
     * @param ?Authentication $auth
     */
    public function send(RequestInterface $request, ?Authentication $auth = null): ResponseInterface
    {
        $auth ??= $this->auth;

        if ($auth !== null) {
            $request = $auth->authenticate($request);
        }

        $client = (new PluginClientFactory())->createClient($this->http, [
            new HeaderDefaultsPlugin($this->headers),
            ...$this->plugins,
        ]);

        return $client->sendRequest($request);
    }

    public function baseUri(): BaseUri
    {
        return $this->baseUri;
    }

    /**
     * The SDK's own headers, read once when the Connector is constructed. The
     * Consumer's `$headers` replace any of the same name.
     *
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => $this->userAgentString(),
        ];
    }

    /**
     * The User-Agent sent with every request: RFC 9110 product tokens, most
     * significant first, e.g. `php-sdk/0.1.0 PHP/8.5.0`.
     *
     * An SDK Author overrides this to put their own SDK in front, so the API
     * provider can tell which SDK and version a request came from:
     *
     *     return 'acme-php/2.1.0 ' . parent::userAgentString();
     */
    protected function userAgentString(): string
    {
        return sprintf('php-sdk/%s PHP/%s', self::VERSION, PHP_VERSION);
    }
}
