<?php

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\Authentication\Bearer;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Tests\SdkTestCase;

uses(SdkTestCase::class);

it('can create a client', function () {
    expect($this->createClient())->toBeInstanceOf(ClientContract::class);
});

it('can setup the HTTP client', function () {
    $client = $this->createClient();

    expect($client->client())->toBeInstanceOf(PluginClient::class);
});

it('can change the plugins', function () {
    $client = $this->createClient();

    $defaultPlugins = $client->plugins();

    expect(array_map('get_class', $client->plugins()))->toBe(array_map('get_class', $client->defaultPlugins()));

    $bearerPlugin = new AuthenticationPlugin(new Bearer('my-bearer'));

    $plugins = $client->plugins([$bearerPlugin]);


    expect($plugins)->toContain($bearerPlugin);
});
