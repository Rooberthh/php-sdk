<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\AuthenticatedClient;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

it('serves data objects to a resource and records the authenticated request', function () {
    $fake = new FakeTransport([
        'GET test' => FakeResponse::json([
            ['id' => 1, 'name' => 'Ada', 'desc' => 'first'],
            ['id' => 2, 'name' => 'Linus', 'desc' => 'second'],
        ]),
    ]);

    $client = new AuthenticatedClient(url: 'https://api.example.com', builder: $fake->builder());

    $objects = $client->tests()->list();

    expect($objects)->toHaveCount(2)
        ->and($objects[0])->toBeInstanceOf(TestDataObject::class)
        ->and($objects[0]->name)->toBe('Ada');

    // The fake sits at the end of the plugin chain, so the recorded request
    // carries the Authorization header the AuthenticationPlugin added.
    $fake->assertSent(
        fn(RequestInterface $request): bool => $request->getHeaderLine('Authorization') === 'Bearer secret-token',
    );
});
