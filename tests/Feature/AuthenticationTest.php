<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Http\Message\Authentication\Bearer;
use Rooberthh\Sdk\Auth\NoAuth;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

it('sends no credentials unless the consumer gives some', function () {
    [$client, $fake] = fakeClient();

    $client->tests()->status();

    expect(sent($fake)->hasHeader('Authorization'))->toBeFalse();
});

it('sends the credentials the consumer gives', function () {
    [$client, $fake] = fakeClient(auth: new Bearer('secret-token'));

    $client->tests()->status();

    expect(sent($fake)->getHeaderLine('Authorization'))->toBe('Bearer secret-token');
});

it('sends one request without credentials when told to', function () {
    [$client, $fake] = fakeClient(auth: new Bearer('secret-token'));

    $client->tests()->status(auth: new NoAuth());
    $client->tests()->status();

    expect(sent($fake, 0)->hasHeader('Authorization'))->toBeFalse()
        ->and(sent($fake, 1)->getHeaderLine('Authorization'))->toBe('Bearer secret-token');
});

it('sends one request with other credentials when told to', function () {
    [$client, $fake] = fakeClient(auth: new Bearer('secret-token'));

    $client->tests()->status(auth: new Bearer('other-token'));

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

    [$client] = fakeClient(auth: new Bearer('secret-token'));
    $client->setup([$spy])->tests()->status();

    expect($seen)->toBe(['Bearer secret-token']);
});

it('keeps each client to its own credentials', function () {
    [$a, $fake] = fakeClient(auth: new Bearer('token-a'));
    $b = (new MockClient('https://api.mock.com', new Bearer('token-b')))->client($fake);

    $a->tests()->status();
    $b->tests()->status();

    expect(sent($fake, 0)->getHeaderLine('Authorization'))->toBe('Bearer token-a')
        ->and(sent($fake, 1)->getHeaderLine('Authorization'))->toBe('Bearer token-b');
});
