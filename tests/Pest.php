<?php

declare(strict_types=1);

use Http\Message\Authentication;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\MockClient;
use Psr\Http\Message\RequestInterface;

/**
 * A client sending through a FakeTransport that answers every request with
 * `{}`, plus the fake, to assert on what went over the wire.
 *
 * @template T of Client
 *
 * @param  class-string<T>     $class
 * @param  string              $url
 * @param  null|Authentication $auth
 * @return array{0: T, 1: FakeTransport}
 */
function fakeClient(string $class = MockClient::class, string $url = 'https://api.mock.com', ?Authentication $auth = null): array
{
    $fake = new FakeTransport(['*' => []]);

    return [(new $class($url, $auth))->client($fake), $fake];
}

/**
 * The one request a FakeTransport recorded, or the nth.
 * @param FakeTransport $fake
 * @param int $index
 */
function sent(FakeTransport $fake, int $index = 0): RequestInterface
{
    return $fake->recorded()[$index];
}
