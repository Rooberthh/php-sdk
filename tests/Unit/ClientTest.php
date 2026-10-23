<?php

declare(strict_types=1);

use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Exception\NetworkException;
use Psr\Http\Message\RequestInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\LateHeaderClient;
use Rooberthh\Sdk\Tests\Stubs\MockClient;
use Rooberthh\Sdk\Tests\Stubs\NonSeekableStream;
use Rooberthh\Sdk\Tests\Stubs\PluginClient;

uses(SdkTestCase::class);

it('can create a client', function () {
    expect($this->createClient())->toBeInstanceOf(ClientContract::class);
});

it('sends the default headers', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test'));

    expect(sent($fake)->getHeaderLine('Accept'))->toBe('application/json')
        ->and(sent($fake)->getHeaderLine('Content-Type'))->toBe('application/json');
});

it('applies plugins added with setup() after a first request', function () {
    [$client, $fake] = fakeClient();

    $client->send($client->createRequest(Method::GET, '/test'));
    $client->setup([new HeaderSetPlugin(['X-Trace' => 'abc'])]);
    $client->send($client->createRequest(Method::GET, '/test'));

    expect(sent($fake, 0)->hasHeader('X-Trace'))->toBeFalse()
        ->and(sent($fake, 1)->getHeaderLine('X-Trace'))->toBe('abc');
});

it('runs the sdk author plugins before the consumer plugins', function () {
    [$client, $fake] = fakeClient(PluginClient::class);

    $client->setup([new HeaderAppendPlugin(['X-Order' => 'consumer'])]);
    $client->send($client->createRequest(Method::GET, '/test'));

    expect(sent($fake)->getHeader('X-Order'))->toBe(['author', 'consumer']);
});

it('keeps its plugins when the transport is swapped', function () {
    [$client] = fakeClient();
    $client->setup([new HeaderSetPlugin(['X-Trace' => 'abc'])]);
    $client->send($client->createRequest(Method::GET, '/test'));

    $other = new FakeTransport(['*' => []]);
    $client->client($other)->send($client->createRequest(Method::GET, '/test'));

    expect(sent($other)->getHeaderLine('X-Trace'))->toBe('abc')
        ->and(sent($other)->getHeaderLine('Accept'))->toBe('application/json');
});

it('reads default headers from a property assigned after parent::__construct()', function () {
    $fake = new FakeTransport(['*' => []]);
    $client = (new LateHeaderClient('https://api.mock.com', apiVersion: '2024-01'))->client($fake);

    $client->send($client->createRequest(Method::GET, '/test'));

    expect(sent($fake)->getHeaderLine('X-Api-Version'))->toBe('2024-01');
});

it('has no last response before anything is sent', function () {
    [$client] = fakeClient();

    expect($client->getLastResponse())->toBeNull();
});

it('returns the same response instance that send() returned', function () {
    [$client] = fakeClient();

    $sent = $client->send($client->createRequest(Method::GET, '/test'));

    expect($client->getLastResponse())->toBe($sent);
});

it('remembers the last response after a call that returned data objects', function () {
    $client = (new MockClient('https://api.mock.com'))->client(new FakeTransport([
        'GET test' => FakeResponse::json(
            [['id' => 1, 'name' => 'A', 'desc' => 'x']],
            headers: ['Link' => '<https://api.mock.com/test?page=2>; rel="next"'],
        ),
    ]));

    $objects = $client->tests()->list();

    expect($objects)->toHaveCount(1)
        ->and($client->getLastResponse())->toBeInstanceOf(Response::class)
        ->and($client->getLastResponse()?->status())->toBe(200)
        ->and($client->getLastResponse()?->header('Link'))
        ->toBe('<https://api.mock.com/test?page=2>; rel="next"');
});

it('keeps the last response readable after a read-once body was consumed', function () {
    $client = (new MockClient('https://api.mock.com'))->client(new FakeTransport([
        '*' => FakeResponse::status(200)->withBody(new NonSeekableStream('[{"id":1,"name":"a","desc":"b"}]')),
    ]));

    $client->tests()->list();

    expect($client->getLastResponse()?->json())->toBe([['id' => 1, 'name' => 'a', 'desc' => 'b']]);
});

it('reports no last response when the request failed', function () {
    $offline = false;

    $client = (new MockClient('https://api.mock.com'))->client(new FakeTransport([
        '*' => function (RequestInterface $request) use (&$offline) {
            if ($offline) {
                throw new NetworkException('offline', $request);
            }

            return FakeResponse::json([]);
        },
    ]));

    $client->send($client->createRequest(Method::GET, '/test'));
    expect($client->getLastResponse())->not->toBeNull();

    $offline = true;

    expect(fn() => $client->send($client->createRequest(Method::GET, '/test')))->toThrow(NetworkException::class)
        ->and($client->getLastResponse())->toBeNull();
});
