<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Auth\ApiKey;
use Rooberthh\Sdk\Auth\BasicAuth;
use Rooberthh\Sdk\Auth\BearerToken;
use Rooberthh\Sdk\Auth\NoAuth;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

it('sends no credentials unless the consumer gives some', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->status();

    expect(sent($fake)->hasHeader('Authorization'))->toBeFalse();
});

it('sends a bearer token', function () {
    [$client, $fake] = fakeClient(auth: new BearerToken('secret-token'));

    $client->tests()->status();

    expect(sent($fake)->getHeaderLine('Authorization'))->toBe('Bearer secret-token');
});

it('sends basic credentials', function () {
    [$client, $fake] = fakeClient(auth: new BasicAuth('ada', 'lovelace'));

    $client->tests()->status();

    expect(sent($fake)->getHeaderLine('Authorization'))->toBe('Basic ' . base64_encode('ada:lovelace'));
});

it('sends an api key in the header the api names', function () {
    [$client, $fake] = fakeClient(auth: ApiKey::header('X-Api-Key', 'k-123'));

    $client->tests()->status();

    expect(sent($fake)->getHeaderLine('X-Api-Key'))->toBe('k-123');
});

it('sends an api key as a query parameter, keeping the query already there', function () {
    [$client, $fake] = fakeClient(auth: ApiKey::query('api_key', 'k 123'));

    $client->send($client->createRequest(Method::GET, '/test?filter.name=a&tag=x&tag=y'));

    expect(sent($fake)->getUri()->getQuery())->toBe('filter.name=a&tag=x&tag=y&api_key=k%20123');
});

it('sends one request without credentials when told to', function () {
    [$client, $fake] = fakeClient(auth: new BearerToken('secret-token'));

    $client->tests()->status(auth: new NoAuth());
    $client->tests()->status();

    expect(sent($fake, 0)->hasHeader('Authorization'))->toBeFalse()
        ->and(sent($fake, 1)->getHeaderLine('Authorization'))->toBe('Bearer secret-token');
});

it('sends one request with other credentials when told to', function () {
    [$client, $fake] = fakeClient(auth: new BearerToken('secret-token'));

    $client->tests()->status(auth: new BearerToken('other-token'));

    expect(sent($fake)->getHeaderLine('Authorization'))->toBe('Bearer other-token');
});

it('authenticates before the plugins see the request', function () {
    $seen = [];

    $spy = new class ($seen) implements Plugin {
        /** @param list<string> $seen */
        public function __construct(private array &$seen) {}

        public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
        {
            $this->seen[] = $request->getHeaderLine('Authorization');

            return $next($request);
        }
    };

    [$client] = fakeClient(auth: new BearerToken('secret-token'));
    $client->setup([$spy])->tests()->status();

    expect($seen)->toBe(['Bearer secret-token']);
});

it('keeps each client to its own credentials', function () {
    [$a, $fake] = fakeClient(auth: new BearerToken('token-a'));
    $b = (new MockClient('https://api.mock.com', new BearerToken('token-b')))->client($fake);

    $a->tests()->status();
    $b->tests()->status();

    expect(sent($fake, 0)->getHeaderLine('Authorization'))->toBe('Bearer token-a')
        ->and(sent($fake, 1)->getHeaderLine('Authorization'))->toBe('Bearer token-b');
});
