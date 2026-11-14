<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Rooberthh\Sdk\Contracts\HasBody;
use Rooberthh\Sdk\Contracts\HasQuery;
use Rooberthh\Sdk\Contracts\RequestContract;
use Rooberthh\Sdk\Http\BaseUri;
use Rooberthh\Sdk\Http\Query;

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
     * @param null|Authentication  $auth applied to every request
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
     * Send a Request and return its {@see RequestContract::result()}.
     *
     * @template T
     *
     * @param RequestContract<T> $request
     *
     * @throws Exceptions\UnsafeUri when the Request's path would leave the base URI
     * @throws Exceptions\InvalidQuery when the Request's Query can't be encoded
     * @throws ClientExceptionInterface when the Transport can't send it
     *
     * @return T
     */
    public function send(RequestContract $request): mixed
    {
        return $request->result($this->raw($request));
    }

    /**
     * Send a Request and return the response as it came back.
     *
     * Authentication is applied before the plugin chain, so every plugin and
     * the Transport see the request as it goes over the wire. The chain is the
     * headers, then the plugins added through {@see self::setup()}.
     *
     * @param RequestContract<mixed> $request
     *
     * @throws Exceptions\UnsafeUri when the Request's path would leave the base URI
     * @throws Exceptions\InvalidQuery when the Request's Query can't be encoded
     * @throws ClientExceptionInterface when the Transport can't send it
     */
    public function raw(RequestContract $request): ResponseInterface
    {
        $psr = $this->toPsr($request);

        if ($this->auth instanceof Authentication) {
            $psr = $this->auth->authenticate($psr);
        }

        $client = new PluginClientFactory()->createClient($this->http, [
            new HeaderDefaultsPlugin($this->headers),
            ...$this->plugins,
        ]);

        return $client->sendRequest($psr);
    }

    /**
     * The PSR-7 request a Request describes, before authentication and plugins.
     * It carries a query only when the Request implements {@see HasQuery}, and
     * a body, with the Body's Content-Type, only when it implements
     * {@see HasBody}. The Content-Type is set on the request itself, so a
     * Connector or Consumer header of the same name can't contradict it.
     *
     * @param RequestContract<mixed> $request
     *
     * @throws Exceptions\UnsafeUri when the path would leave the base URI
     * @throws Exceptions\InvalidQuery when the Query can't be encoded
     */
    private function toPsr(RequestContract $request): RequestInterface
    {
        $uri = $this->baseUri->resolve($request->path());

        if ($request instanceof HasQuery && !($query = new Query($request->query()))->isEmpty()) {
            $uri = $uri->withQuery($query->toString());
        }

        $psr = $this->requestFactory->createRequest($request->method()->value, $uri->toString());

        if (!$request instanceof HasBody) {
            return $psr;
        }

        $body = $request->body();

        return $psr
            ->withBody(is_string($content = $body->content()) ? $this->streamFactory->createStream($content) : $content)
            ->withHeader('Content-Type', $body->contentType());
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
            'Accept'     => 'application/json',
            'User-Agent' => $this->userAgentString(),
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
