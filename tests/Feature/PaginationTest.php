<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Response;
use Rooberthh\Sdk\Exceptions\PaginationFailed;
use Rooberthh\Sdk\Http\Builder;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;
use Rooberthh\Sdk\Tests\Stubs\MockSDK;
use Rooberthh\Sdk\Tests\Stubs\NonSeekableStream;

uses(SdkTestCase::class);

/**
 * @param list<string> $bodies one JSON body per page
 */
function paginating(array $bodies): array
{
    $mock = new MockClient();

    foreach ($bodies as $body) {
        $mock->addResponse(new Response(200, [], $body));
    }

    return [new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock)), $mock];
}

function card(int $id): string
{
    return sprintf('{"id":%d,"name":"Card %d","desc":"A card."}', $id, $id);
}

it('walks pages until one comes back short', function () {
    [$client] = paginating([
        '[' . card(1) . ',' . card(2) . ']',
        '[' . card(3) . ',' . card(4) . ']',
        '[' . card(5) . ']',
    ]);

    $ids = [];

    foreach ($client->tests()->paged() as $object) {
        expect($object)->toBeInstanceOf(TestDataObject::class);
        $ids[] = $object->id;
    }

    expect($ids)->toBe([1, 2, 3, 4, 5]);
});

it('sends the page and per_page parameters', function () {
    [$client, $mock] = paginating([
        '[' . card(1) . ',' . card(2) . ']',
        '[' . card(3) . ']',
    ]);

    $client->tests()->paged()->all();

    $queries = array_map(
        static fn($request) => $request->getUri()->getQuery(),
        $mock->getRequests(),
    );

    expect($queries)->toBe(['page=1&per_page=2', 'page=2&per_page=2']);
});

it('advances the offset by the limit', function () {
    [$client, $mock] = paginating([
        '{"data":[' . card(1) . ',' . card(2) . ']}',
        '{"data":[' . card(3) . ']}',
    ]);

    $objects = $client->tests()->offset()->all();

    $queries = array_map(
        static fn($request) => $request->getUri()->getQuery(),
        $mock->getRequests(),
    );

    expect($objects)->toHaveCount(3)
        ->and($queries)->toBe(['offset=0&limit=2', 'offset=2&limit=2']);
});

it('reads entries out of an envelope', function () {
    [$client] = paginating(['{"data":[' . card(1) . ']}']);

    expect($client->tests()->offset()->all())->toHaveCount(1);
});

it('stops on the first short page without asking for another', function () {
    [$client, $mock] = paginating(['[' . card(1) . ']']);

    $client->tests()->paged()->all();

    expect($mock->getRequests())->toHaveCount(1);
});

it('fetches nothing until you iterate', function () {
    [$client, $mock] = paginating(['[' . card(1) . ']']);

    $paginator = $client->tests()->paged();

    expect($mock->getRequests())->toHaveCount(0);

    $paginator->all();

    expect($mock->getRequests())->toHaveCount(1);
});

it('gives access to each page as a response', function () {
    [$client] = paginating([
        '[' . card(1) . ',' . card(2) . ']',
        '[' . card(3) . ']',
    ]);

    $statuses = [];

    foreach ($client->tests()->paged()->pages() as $response) {
        $statuses[] = $response->status();
    }

    expect($statuses)->toBe([200, 200]);
});

it('refuses to page forever', function () {
    $mock = new MockClient();
    $mock->addResponse(new Response(200, [], '[' . card(1) . ',' . card(2) . ']'));
    $mock->setDefaultResponse(new Response(200, [], '[' . card(1) . ',' . card(2) . ']'));

    $client = new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock));

    expect(fn() => $client->tests()->paged(perPage: 2)->all())
        ->toThrow(PaginationFailed::class, 'Stopped after');
})->skip(fn() => !method_exists(MockClient::class, 'setDefaultResponse'), 'mock client cannot repeat responses');

it('paginates over bodies that can only be read once', function () {
    $mock = new MockClient();
    $mock->addResponse(new Response(200, [], new NonSeekableStream('[' . card(1) . ',' . card(2) . ']')));
    $mock->addResponse(new Response(200, [], new NonSeekableStream('[' . card(3) . ']')));

    $client = new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock));

    $ids = array_map(static fn($object) => $object->id, $client->tests()->paged()->all());

    expect($ids)->toBe([1, 2, 3]);
});
