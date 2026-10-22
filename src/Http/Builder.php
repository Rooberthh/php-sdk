<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Assembles the Transport an SDK sends through.
 *
 * Plugins can be added and removed at any point; the composed Transport is built
 * on first use and rebuilt whenever the plugin list changes. This is the seam
 * for anything that needs to sit between the SDK and the network — recording,
 * logging, retries, caching.
 *
 * Every collaborator is optional and falls back to discovery, so a bare
 * `new Builder()` works, and tests can inject a mock Transport instead.
 */
final class Builder
{
    private readonly ClientInterface $httpClient;

    private readonly RequestFactoryInterface $requestFactory;

    private readonly StreamFactoryInterface $streamFactory;

    private readonly UriFactoryInterface $uriFactory;

    /** @var list<Plugin> */
    private array $plugins = [];

    private ?ClientInterface $pluginClient = null;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?UriFactoryInterface $uriFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->uriFactory = $uriFactory ?? Psr17FactoryDiscovery::findUriFactory();
    }

    /**
     * The Transport with every plugin applied, built once and reused.
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->pluginClient ??= (new PluginClientFactory())->createClient(
            $this->httpClient,
            $this->plugins,
        );
    }

    /**
     * Add a plugin to the end of the chain.
     * @param Plugin $plugin
     */
    public function addPlugin(Plugin $plugin): void
    {
        $this->plugins[] = $plugin;
        $this->pluginClient = null;
    }

    /**
     * Remove every plugin of the given class.
     *
     * @param class-string<Plugin> $fqcn
     */
    public function removePlugin(string $fqcn): void
    {
        foreach ($this->plugins as $index => $plugin) {
            if ($plugin instanceof $fqcn) {
                unset($this->plugins[$index]);
                $this->pluginClient = null;
            }
        }

        $this->plugins = array_values($this->plugins);
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }
}
