<?php

declare(strict_types=1);

use Http\Client\Common\PluginClient;
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
