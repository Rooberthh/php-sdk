<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\Enums\Status;
use Rooberthh\Sdk\Tests\Stubs\MockClient;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestLinePayload;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestPayload;

uses(SdkTestCase::class);

it('joins a uri with a leading slash onto the base url', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test'));

    expect((string) sent($fake)->getUri())->toBe('https://api.mock.com/test');
});

it('joins a uri without a leading slash onto the base url', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, 'test'));

    expect((string) sent($fake)->getUri())->toBe('https://api.mock.com/test');
});

it('does not double the slash when the base url ends in one', function () {
    [$client, $fake] = fakeClient(url: 'https://api.mock.com/');

    $client->send($client->createRequest(Method::GET, '/test'));

    expect((string) sent($fake)->getUri())->toBe('https://api.mock.com/test');
});

it('puts the query onto the request', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test', query: ['status' => 'active', 'page' => 1]));

    expect(sent($fake)->getUri()->getQuery())->toBe('status=active&page=1');
});

it('merges the query over a query already in the uri', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test?status=archived&sort=name', query: ['status' => 'active']));

    parse_str(sent($fake)->getUri()->getQuery(), $params);

    expect($params)->toBe(['status' => 'active', 'sort' => 'name']);
});

it('leaves a query already in the uri untouched when given none', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test?filter.name=a&tag=x&tag=y'));

    expect(sent($fake)->getUri()->getQuery())->toBe('filter.name=a&tag=x&tag=y');
});

it('omits null query values', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test', query: ['status' => 'active', 'q' => null]));

    expect(sent($fake)->getUri()->getQuery())->toBe('status=active');
});

it('sends a payload object as snake_case json', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->store(new CreateTestPayload(
        purchaseCountry: 'SE',
        orderLines: [new CreateTestLinePayload(name: 'Shoes', unitPrice: 49900)],
        status: Status::Active,
        merchantReference1: 'order-123',
    ));

    $body = json_decode((string) sent($fake)->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($body)->toBe([
        'purchase_country'    => 'SE',
        'order_lines'         => [['name' => 'Shoes', 'unit_price' => 49900]],
        'status'              => 'active',
        'merchant_reference1' => 'order-123',
    ]);
});

it('omits null properties from a payload object', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->store(new CreateTestPayload(purchaseCountry: 'SE'));

    $body = json_decode((string) sent($fake)->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($body)
        ->toBe(['purchase_country' => 'SE', 'order_lines' => []])
        ->not->toHaveKey('status')
        ->not->toHaveKey('tags');
});

it('sends an array body as given', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->store(['already' => 'shaped']);

    expect((string) sent($fake)->getBody())->toBe('{"already":"shaped"}');
});

it('sends a string body untouched', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->store('<order><id>1</id></order>');

    expect((string) sent($fake)->getBody())->toBe('<order><id>1</id></order>');
});

it('lets a header set on the request override the default', function () {
    [$client, $fake] = fakeClient();

    $client->send(
        $client->createRequest(Method::POST, '/test')
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($client->createPayload('<order/>')),
    );

    expect(sent($fake)->getHeaderLine('Content-Type'))->toBe('application/xml');
});

it('builds the same request from a resource as from the client', function () {
    [$client, $fake] = fakeClient(MockClient::class);

    $client->tests()->list();

    expect((string) sent($fake)->getUri())->toBe('https://api.mock.com/test?name=asc')
        ->and(sent($fake)->getMethod())->toBe('GET');
});
