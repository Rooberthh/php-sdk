<?php

use Http\Client\Common\PluginClient;
use Http\Mock\Client as MockClient;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Tests\SdkTestCase;

uses(SdkTestCase::class);

it('can create a client', function () {
    expect($this->createClient())->toBeInstanceOf(ClientContract::class);
});

it('can setup the HTTP client', function () {
    $client = $this->createClient();

    expect($client->http)->toBeNull();

    $client = $client->setup();

    expect($client->http)->toBeInstanceOf(PluginClient::class);
});

it('can setup swap the HTTP client', function () {
    $client = $this->createClient()->setup();

    expect($client->http)->toBeInstanceOf(PluginClient::class);

    $client->client(new MockClient());

    expect($client->http)->toBeInstanceOf(MockClient::class);
});
