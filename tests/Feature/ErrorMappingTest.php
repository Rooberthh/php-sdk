<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;
use Rooberthh\Sdk\Tests\Stubs\ErrorMappingClient;
use Rooberthh\Sdk\Tests\Stubs\Exceptions\NotFound;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

function errorClient(FakeTransport $fake): ErrorMappingClient
{
    return new ErrorMappingClient(url: 'https://api.example.com', builder: $fake->builder());
}

it('throws the mapped exception when throw() hits a mapped status', function () {
    $client = errorClient(new FakeTransport(['*' => FakeResponse::status(404, 'Not Found')]));

    $response = $client->request(Method::GET, 'missing');

    try {
        $response->throw();
        $this->fail('Expected NotFound to be thrown.');
    } catch (NotFound $exception) {
        expect($exception->response)->toBe($response);
    }
});

it('throws the mapped exception when a data object is asked of a mapped status', function () {
    $client = errorClient(new FakeTransport(['*' => FakeResponse::status(404, 'Not Found')]));

    expect(fn() => $client->request(Method::GET, 'missing')->dataObject(TestDataObject::class))
        ->toThrow(NotFound::class);
});

it('falls back to RequestFailed for a status the client does not map', function () {
    $client = errorClient(new FakeTransport(['*' => FakeResponse::status(500, 'boom')]));

    expect(fn() => $client->request(Method::GET, 'broken')->throw())
        ->toThrow(RequestFailed::class);
});

it('throws RequestFailed from a client that does not override the hook', function () {
    $client = new MockClient(
        url: 'https://api.example.com',
        builder: (new FakeTransport(['*' => FakeResponse::status(404, 'Not Found')]))->builder(),
    );

    expect(fn() => $client->request(Method::GET, 'missing')->throw())
        ->toThrow(RequestFailed::class);
});

it('does not consult the hook for a successful response', function () {
    $client = errorClient(new FakeTransport([
        '*' => FakeResponse::json(['id' => 1, 'name' => 'A', 'desc' => 'x']),
    ]));

    $object = $client->request(Method::GET, 'ok')->dataObject(TestDataObject::class);

    expect($object->id)->toBe(1)
        ->and($client->calls)->toBe(0);
});
