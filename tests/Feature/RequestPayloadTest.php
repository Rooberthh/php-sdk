<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Response;
use Rooberthh\Sdk\Http\Builder;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\Enums\Status;
use Rooberthh\Sdk\Tests\Stubs\MockSDK;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestLinePayload;
use Rooberthh\Sdk\Tests\Stubs\Payloads\CreateTestPayload;

uses(SdkTestCase::class);

/**
 * Calls the given resource method and returns the body that actually went
 * over the wire.
 * @param string $method
 * @param mixed $payload
 */
function sentBody(string $method, mixed $payload): string
{
    $mock = new MockClient();
    $mock->addResponse(new Response(200, [], '{}'));

    (new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock)))
        ->tests()
        ->{$method}($payload);

    return (string) $mock->getRequests()[0]->getBody();
}

/**
 * @param string $method
 * @param mixed $payload
 * @return array<mixed, mixed>
 */
function sentJson(string $method, mixed $payload): array
{
    return json_decode(sentBody($method, $payload), true, flags: JSON_THROW_ON_ERROR);
}

it('sends a payload object as snake_case json', function () {
    $body = sentJson('store', new CreateTestPayload(
        purchaseCountry: 'SE',
        orderLines: [new CreateTestLinePayload(name: 'Shoes', unitPrice: 49900)],
        status: Status::Active,
        merchantReference1: 'order-123',
    ));

    expect($body)->toBe([
        'purchase_country'    => 'SE',
        'order_lines'         => [['name' => 'Shoes', 'unit_price' => 49900]],
        'status'              => 'active',
        'merchant_reference1' => 'order-123',
    ]);
});

it('omits null properties from the payload it sends', function () {
    $body = sentJson('store', new CreateTestPayload(purchaseCountry: 'SE'));

    expect($body)
        ->toBe(['purchase_country' => 'SE', 'order_lines' => []])
        ->not->toHaveKey('status')
        ->not->toHaveKey('tags');
});

it('sends an array as given', function () {
    expect(sentJson('storeJson', ['already' => 'shaped']))->toBe(['already' => 'shaped']);
});

it('sends a string body untouched', function () {
    expect(sentBody('storeRaw', '<order><id>1</id></order>'))
        ->toBe('<order><id>1</id></order>');
});
