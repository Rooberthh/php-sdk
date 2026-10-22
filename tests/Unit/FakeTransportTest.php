<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Unit;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Testing\FakeResponse;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Testing\UnexpectedRequest;

function sendRequest(FakeTransport $fake, string $method, string $url): void
{
    $fake->sendRequest(new Request($method, $url));
}

it('matches a method-prefixed key only for that method', function () {
    $fake = new FakeTransport(['POST users' => FakeResponse::status(201)]);

    expect($fake->sendRequest(new Request('POST', 'https://api.test/users'))->getStatusCode())->toBe(201);

    expect(fn() => sendRequest($fake, 'GET', 'https://api.test/users'))
        ->toThrow(UnexpectedRequest::class);
});

it('matches a key without a method for any method', function () {
    $fake = new FakeTransport(['users' => FakeResponse::status(204)]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/users'))->getStatusCode())->toBe(204);
    expect($fake->sendRequest(new Request('DELETE', 'https://api.test/users'))->getStatusCode())->toBe(204);
});

it('compares the method case-insensitively', function () {
    $fake = new FakeTransport(['get users' => FakeResponse::status(200)]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/users'))->getStatusCode())->toBe(200);
});

it('matches * across slashes', function () {
    $fake = new FakeTransport(['users/*' => FakeResponse::status(200)]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/users/1/orders/2'))->getStatusCode())->toBe(200);
});

it('matches a bare * against every request', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/anything/at/all'))->getStatusCode())->toBe(200);
});

it('ignores the query string when matching', function () {
    $fake = new FakeTransport(['GET users/1' => FakeResponse::status(200)]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/users/1?include=orders'))->getStatusCode())->toBe(200);
});

it('ignores a leading slash on either side', function () {
    $fake = new FakeTransport(['/users/1' => FakeResponse::status(200)]);

    // The request path keeps its leading slash; the key carries one too.
    expect($fake->sendRequest(new Request('GET', 'https://api.test/users/1'))->getStatusCode())->toBe(200);
});

it('lets the first matching key win', function () {
    $fake = new FakeTransport([
        'users/*' => FakeResponse::status(200),
        '*'       => FakeResponse::status(404),
    ]);

    expect($fake->sendRequest(new Request('GET', 'https://api.test/users/1'))->getStatusCode())->toBe(200);
});

it('treats an array value as a 200 json response', function () {
    $fake = new FakeTransport(['GET users/1' => ['id' => 1, 'name' => 'Ada']]);

    $response = $fake->sendRequest(new Request('GET', 'https://api.test/users/1'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string) $response->getBody(), true))->toBe(['id' => 1, 'name' => 'Ada']);
});

it('passes the request to a closure value', function () {
    $seen = null;

    $fake = new FakeTransport([
        '*' => function (RequestInterface $request) use (&$seen) {
            $seen = $request;

            return FakeResponse::json(['method' => $request->getMethod()]);
        },
    ]);

    $response = $fake->sendRequest(new Request('PATCH', 'https://api.test/users/1'));

    expect($seen)->toBeInstanceOf(RequestInterface::class)
        ->and(json_decode((string) $response->getBody(), true))->toBe(['method' => 'PATCH']);
});

it('throws UnexpectedRequest listing the keys when nothing matches', function () {
    $fake = new FakeTransport([
        'GET users/1' => FakeResponse::status(200),
        'POST users'  => FakeResponse::status(201),
    ]);

    expect(fn() => sendRequest($fake, 'DELETE', 'https://api.test/orders/9'))
        ->toThrow(
            UnexpectedRequest::class,
            null,
        );

    try {
        $fake->sendRequest(new Request('DELETE', 'https://api.test/orders/9'));
    } catch (UnexpectedRequest $exception) {
        expect($exception->getMessage())
            ->toContain('DELETE')
            ->toContain('https://api.test/orders/9')
            ->toContain('GET users/1')
            ->toContain('POST users');
    }
});

it('records every request', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);

    $fake->sendRequest(new Request('GET', 'https://api.test/a'));
    $fake->sendRequest(new Request('POST', 'https://api.test/b'));

    expect($fake->recorded())->toHaveCount(2)
        ->and($fake->recorded()[0]->getMethod())->toBe('GET')
        ->and($fake->recorded()[1]->getMethod())->toBe('POST');
});

it('passes and fails assertSent', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);
    $fake->sendRequest(new Request('POST', 'https://api.test/users'));

    $fake->assertSent(fn(RequestInterface $r) => $r->getMethod() === 'POST');

    expect(fn() => $fake->assertSent(fn(RequestInterface $r) => $r->getMethod() === 'DELETE'))
        ->toThrow(AssertionFailedError::class);
});

it('passes and fails assertNotSent', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);
    $fake->sendRequest(new Request('POST', 'https://api.test/users'));

    $fake->assertNotSent(fn(RequestInterface $r) => $r->getMethod() === 'DELETE');

    expect(fn() => $fake->assertNotSent(fn(RequestInterface $r) => $r->getMethod() === 'POST'))
        ->toThrow(AssertionFailedError::class);
});

it('passes and fails assertSentCount', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);
    $fake->sendRequest(new Request('GET', 'https://api.test/a'));

    $fake->assertSentCount(1);

    expect(fn() => $fake->assertSentCount(2))
        ->toThrow(AssertionFailedError::class);
});

it('passes and fails assertNothingSent', function () {
    $fake = new FakeTransport(['*' => FakeResponse::status(200)]);

    $fake->assertNothingSent();

    $fake->sendRequest(new Request('GET', 'https://api.test/a'));

    expect(fn() => $fake->assertNothingSent())
        ->toThrow(AssertionFailedError::class);
});
