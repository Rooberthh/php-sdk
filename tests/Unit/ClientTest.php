<?php

declare(strict_types=1);

use Http\Client\Common\Plugin\HeaderSetPlugin;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Exception\NetworkException;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response as Psr7Response;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Http\Builder;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\AuthenticatedSDK;
use Rooberthh\Sdk\Tests\Stubs\MockSDK;
use Rooberthh\Sdk\Tests\Stubs\NonSeekableStream;

uses(SdkTestCase::class);

/**
 * A client over a mock transport. The mock sits *inside* the plugin chain, so
 * whatever it records is the request as it would go over the wire.
 *
 * @param string $class
 * @param int $responses
 * @return array{0: AuthenticatedSDK|MockSDK, 1: MockClient}
 */
function wired(string $class = MockSDK::class, int $responses = 1): array
{
    $mock = new MockClient();

    for ($i = 0; $i < $responses; $i++) {
        $mock->addResponse(new Psr7Response(200, [], '{}'));
    }

    return [new $class(url: 'https://api.mock.com', builder: new Builder($mock)), $mock];
}

function get(string $path = '/test'): Request
{
    return new Request('GET', "https://api.mock.com{$path}");
}

it('can create a client', function () {
    expect($this->createClient())->toBeInstanceOf(ClientContract::class);
});

it('exposes its builder', function () {
    expect($this->createClient()->builder())->toBeInstanceOf(Builder::class);
});

it('sends the default headers', function () {
    [$client, $mock] = wired();

    $client->send(get());

    expect($mock->getRequests()[0]->getHeaderLine('Accept'))->toBe('application/json')
        ->and($mock->getRequests()[0]->getHeaderLine('Content-Type'))->toBe('application/json');
});

it('sends the authentication the client declares', function () {
    [$client, $mock] = wired(AuthenticatedSDK::class);

    $client->send(get());

    expect($mock->getRequests()[0]->getHeaderLine('Authorization'))->toBe('Bearer secret-token');
});

it('applies plugins added after construction', function () {
    [$client, $mock] = wired(responses: 2);

    $client->send(get());
    expect($mock->getRequests()[0]->hasHeader('X-Trace'))->toBeFalse();

    $client->builder()->addPlugin(new HeaderSetPlugin(['X-Trace' => 'abc']));
    $client->send(get());

    expect($mock->getRequests()[1]->getHeaderLine('X-Trace'))->toBe('abc');
});

it('stops applying a plugin once it is removed', function () {
    [$client, $mock] = wired(responses: 2);

    $client->builder()->addPlugin(new HeaderSetPlugin(['X-Trace' => 'abc']));
    $client->send(get());
    expect($mock->getRequests()[0]->getHeaderLine('X-Trace'))->toBe('abc');

    $client->builder()->removePlugin(HeaderSetPlugin::class);
    $client->send(get());

    expect($mock->getRequests()[1]->hasHeader('X-Trace'))->toBeFalse();
});

it('leaves other plugins in place when one is removed', function () {
    [$client, $mock] = wired();

    $client->builder()->removePlugin(RedirectPlugin::class);
    $client->send(get());

    expect($mock->getRequests()[0]->getHeaderLine('Accept'))->toBe('application/json');
});

it('has no last response before anything is sent', function () {
    [$client] = wired();

    expect($client->getLastResponse())->toBeNull();
});

it('remembers the last response after a call that returned data objects', function () {
    $mock = new MockClient();
    $mock->addResponse(new Psr7Response(
        200,
        ['Link' => '<https://api.mock.com/test?page=2>; rel="next"'],
        '[{"id":1,"name":"A","desc":"x"}]',
    ));

    $client = new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock));

    $objects = $client->tests()->list();

    expect($objects)->toHaveCount(1)
        ->and($client->getLastResponse())->toBeInstanceOf(Response::class)
        ->and($client->getLastResponse()?->status())->toBe(200)
        ->and($client->getLastResponse()?->header('Link'))
        ->toBe('<https://api.mock.com/test?page=2>; rel="next"');
});

it('keeps the last response readable after a read-once body was consumed', function () {
    $mock = new MockClient();
    $mock->addResponse(new Psr7Response(200, [], new NonSeekableStream('[{"id":1,"name":"a","desc":"b"}]')));

    $client = new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock));

    $client->tests()->list();

    expect($client->getLastResponse()?->json())->toBe([['id' => 1, 'name' => 'a', 'desc' => 'b']]);
});

it('returns the same response instance that send() returned', function () {
    [$client] = wired();

    $sent = $client->send(get());

    expect($client->getLastResponse())->toBe($sent);
});

it('reports no last response when the request failed', function () {
    $mock = new MockClient();
    $mock->addResponse(new Psr7Response(200, [], '{}'));

    $client = new MockSDK(url: 'https://api.mock.com', builder: new Builder($mock));
    $client->send(get());
    expect($client->getLastResponse())->not->toBeNull();

    $mock->addException(new NetworkException('offline', get()));

    expect(fn() => $client->send(get()))->toThrow(NetworkException::class)
        ->and($client->getLastResponse())->toBeNull();
});
