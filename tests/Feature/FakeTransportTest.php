<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Auth\BearerToken;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

it('serves data objects to a resource and records the authenticated request', function () {
    $fake = new FakeTransport([
        'GET test' => FakeResponse::json([
            ['id' => 1, 'name' => 'Ada', 'desc' => 'first'],
            ['id' => 2, 'name' => 'Linus', 'desc' => 'second'],
        ]),
    ]);

    $client = (new MockClient('https://api.example.com', new BearerToken('secret-token')))->client($fake);

    $objects = $client->tests()->list();

    expect($objects)->toHaveCount(2)
        ->and($objects[0])->toBeInstanceOf(TestDataObject::class)
        ->and($objects[0]->name)->toBe('Ada');

    // The fake sits at the end of the plugin chain, so the recorded request
    // carries the credentials exactly as they went over the wire.
    $fake->assertSent(
        fn(RequestInterface $request): bool => $request->getHeaderLine('Authorization') === 'Bearer secret-token',
    );
});
