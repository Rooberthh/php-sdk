<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Http\Mock\Client as MockTransport;
use Nyholm\Psr7\Response;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Http\Builder;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\Enums\Status;
use Rooberthh\Sdk\Tests\Stubs\MockClient;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestLinePayload;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestPayload;

uses(SdkTestCase::class);

/**
 * A client over a mock transport, plus the mock it records on.
 *
 * @param string $url
 * @return array{0: MockClient, 1: MockTransport}
 */
function overMock(string $url = 'https://api.mock.com'): array
{
    $mock = new MockTransport();
    $mock->addResponse(new Response(200, [], '{}'));

    return [new MockClient(url: $url, builder: new Builder($mock)), $mock];
}

it('joins a uri with a leading slash onto the base url', function () {
    [$client, $mock] = overMock();

    $client->request(Method::GET, '/test');

    expect((string) $mock->getRequests()[0]->getUri())->toBe('https://api.mock.com/test');
});

it('joins a uri without a leading slash onto the base url', function () {
    [$client, $mock] = overMock();

    $client->request(Method::GET, 'test');

    expect((string) $mock->getRequests()[0]->getUri())->toBe('https://api.mock.com/test');
});

it('does not double the slash when the base url ends in one', function () {
    [$client, $mock] = overMock('https://api.mock.com/');

    $client->request(Method::GET, '/test');

    expect((string) $mock->getRequests()[0]->getUri())->toBe('https://api.mock.com/test');
});

it('puts the query onto the request', function () {
    [$client, $mock] = overMock();

    $client->request(Method::GET, '/test', query: ['status' => 'active', 'page' => 1]);

    expect($mock->getRequests()[0]->getUri()->getQuery())->toBe('status=active&page=1');
});

it('merges the query over a query already in the uri', function () {
    [$client, $mock] = overMock();

    $client->request(Method::GET, '/test?status=archived&sort=name', query: ['status' => 'active']);

    parse_str($mock->getRequests()[0]->getUri()->getQuery(), $params);

    expect($params)->toBe(['status' => 'active', 'sort' => 'name']);
});

it('omits null query values', function () {
    [$client, $mock] = overMock();

    $client->request(Method::GET, '/test', query: ['status' => 'active', 'q' => null]);

    expect($mock->getRequests()[0]->getUri()->getQuery())->toBe('status=active');
});

it('sends a payload object as snake_case json', function () {
    [$client, $mock] = overMock();

    $client->request(Method::POST, '/test', body: new CreateTestPayload(
        purchaseCountry: 'SE',
        orderLines: [new CreateTestLinePayload(name: 'Shoes', unitPrice: 49900)],
        status: Status::Active,
        merchantReference1: 'order-123',
    ));

    $body = json_decode((string) $mock->getRequests()[0]->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($body)->toBe([
        'purchase_country'    => 'SE',
        'order_lines'         => [['name' => 'Shoes', 'unit_price' => 49900]],
        'status'              => 'active',
        'merchant_reference1' => 'order-123',
    ]);
});

it('omits null properties from a payload object', function () {
    [$client, $mock] = overMock();

    $client->request(Method::POST, '/test', body: new CreateTestPayload(purchaseCountry: 'SE'));

    $body = json_decode((string) $mock->getRequests()[0]->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($body)
        ->toBe(['purchase_country' => 'SE', 'order_lines' => []])
        ->not->toHaveKey('status')
        ->not->toHaveKey('tags');
});

it('sends an array body as given', function () {
    [$client, $mock] = overMock();

    $client->request(Method::POST, '/test', body: ['already' => 'shaped']);

    expect((string) $mock->getRequests()[0]->getBody())->toBe('{"already":"shaped"}');
});

it('sends a string body untouched', function () {
    [$client, $mock] = overMock();

    $client->request(Method::POST, '/test', body: '<order><id>1</id></order>');

    expect((string) $mock->getRequests()[0]->getBody())->toBe('<order><id>1</id></order>');
});

it('lets a per-call header override the default', function () {
    [$client, $mock] = overMock();

    $client->request(Method::POST, '/test', body: '<order/>', headers: ['Content-Type' => 'application/xml']);

    expect($mock->getRequests()[0]->getHeaderLine('Content-Type'))->toBe('application/xml');
});

it('returns the response that getLastResponse() then reports', function () {
    [$client] = overMock();

    $response = $client->request(Method::GET, '/test');

    expect($client->getLastResponse())->toBe($response);
});
