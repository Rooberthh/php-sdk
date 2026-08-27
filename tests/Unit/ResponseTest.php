<?php

declare(strict_types=1);

use Nyholm\Psr7\Response as Psr7Response;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Exceptions\SdkException;
use Rooberthh\Sdk\Exceptions\UnableToReadResponse;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;
use Psr\Http\Message\ResponseInterface;

function response(int $status = 200, string $body = '{}', array $headers = []): Response
{
    return new Response(new Psr7Response($status, $headers, $body));
}

it('exposes the underlying psr response', function () {
    expect(response()->psr())->toBeInstanceOf(ResponseInterface::class);
});

it('reads the status and body', function () {
    $response = response(201, '{"id":1}');

    expect($response->status())->toBe(201)
        ->and($response->body())->toBe('{"id":1}');
});

it('decodes the json body', function () {
    expect(response(body: '{"id":1,"name":"Test"}')->json())
        ->toBe(['id' => 1, 'name' => 'Test']);
});

it('reads response headers', function () {
    $response = response(headers: ['X-RateLimit-Remaining' => '42']);

    expect($response->header('X-RateLimit-Remaining'))->toBe('42')
        ->and($response->header('X-Not-Sent'))->toBeNull()
        ->and($response->headers())->toHaveKey('X-RateLimit-Remaining');
});

it('reports success and failure without throwing', function () {
    expect(response(200)->successful())->toBeTrue()
        ->and(response(204)->failed())->toBeFalse()
        ->and(response(404)->failed())->toBeTrue()
        ->and(response(500)->successful())->toBeFalse();
});

it('only throws when asked to', function () {
    expect(response(404)->status())->toBe(404);

    expect(fn() => response(404, 'Not Found')->throw())
        ->toThrow(RequestFailed::class, 'The API responded with 404');
});

it('returns itself when throw is called on a successful response', function () {
    $response = response(200);

    expect($response->throw())->toBe($response);
});

it('carries the response on the failure exception', function () {
    try {
        response(422, '{"error":"nope"}')->throw();
    } catch (RequestFailed $exception) {
        expect($exception)->toBeInstanceOf(SdkException::class)
            ->and($exception->response->json())->toBe(['error' => 'nope']);
    }
});

it('explains itself when the body is not json', function () {
    expect(fn() => response(502, '<html>Bad Gateway</html>')->json())
        ->toThrow(UnableToReadResponse::class, 'Expected a JSON body but the API responded with 502');
});

it('builds a data object from the body', function () {
    $object = response(body: '{"id":1,"name":"Test","desc":"A card."}')
        ->dto(TestDataObject::class);

    expect($object)->toBeInstanceOf(TestDataObject::class)
        ->id->toBe(1);
});

it('builds a data object per entry of a list body', function () {
    $objects = response(body: '[{"id":1,"name":"A","desc":"x"},{"id":2,"name":"B","desc":"y"}]')
        ->dtos(TestDataObject::class);

    expect($objects)->toHaveCount(2)
        ->and($objects[1])->toBeInstanceOf(TestDataObject::class)
        ->name->toBe('B');
});
