<?php

declare(strict_types=1);

use Nyholm\Psr7\Response as Psr7Response;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Exceptions\SdkException;
use Rooberthh\Sdk\Exceptions\UnableToReadResponse;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;
use Rooberthh\Sdk\Tests\Stubs\NonSeekableStream;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

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

it('throws the exception the mapping closure returns for a failed response', function () {
    $mapped = new RuntimeException('mapped');

    $response = new Response(new Psr7Response(404, [], 'Not Found'), fn(): Throwable => $mapped);

    expect(fn() => $response->throw())->toThrow($mapped);
});

it('falls back to RequestFailed when the mapping closure returns null', function () {
    $response = new Response(new Psr7Response(500, [], 'boom'), fn(): ?Throwable => null);

    expect(fn() => $response->throw())->toThrow(RequestFailed::class);
});

it('throws RequestFailed when constructed without a mapping closure', function () {
    expect(fn() => (new Response(new Psr7Response(404, [], 'Not Found')))->throw())
        ->toThrow(RequestFailed::class);
});

it('never consults the mapping closure for a successful response', function () {
    $response = new Response(
        new Psr7Response(200, [], '{"id":1,"name":"A","desc":"x"}'),
        function (): Throwable {
            throw new RuntimeException('the hook should not run for a 2xx response');
        },
    );

    expect($response->dataObject(TestDataObject::class)->id)->toBe(1);
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

it('throws the failed request rather than build a data object', function () {
    $response = response(404, '{"message":"Not found"}');

    try {
        $response->dataObject(TestDataObject::class);
        $this->fail('Expected RequestFailed to be thrown.');
    } catch (RequestFailed $exception) {
        expect($exception->getMessage())->toContain('404')
            ->and($exception->response)->toBe($response);
    }
});

it('throws the failed request rather than build a list of data objects', function () {
    $response = response(404, '{"message":"Not found"}');

    try {
        $response->dataObjects(TestDataObject::class);
        $this->fail('Expected RequestFailed to be thrown.');
    } catch (RequestFailed $exception) {
        expect($exception->getMessage())->toContain('404')
            ->and($exception->response)->toBe($response);
    }
});

it('throws the failed request rather than build a page', function () {
    $response = response(404, '{"message":"Not found"}');

    try {
        $response->paginated(TestDataObject::class);
        $this->fail('Expected RequestFailed to be thrown.');
    } catch (RequestFailed $exception) {
        expect($exception->getMessage())->toContain('404')
            ->and($exception->response)->toBe($response);
    }
});

it('throws the failed request rather than fail to read a non-json error page', function () {
    expect(fn() => response(500, '<html>oops</html>')->dataObject(TestDataObject::class))
        ->toThrow(RequestFailed::class);
});

it('still reads the body of a failed response with json()', function () {
    expect(response(422, '{"error":"nope"}')->json())->toBe(['error' => 'nope']);
});

it('builds a data object from the body', function () {
    $object = response(body: '{"id":1,"name":"Test","desc":"A card."}')
        ->dataObject(TestDataObject::class);

    expect($object)->toBeInstanceOf(TestDataObject::class)
        ->id->toBe(1);
});

it('builds a data object per entry of a list body', function () {
    $objects = response(body: '[{"id":1,"name":"A","desc":"x"},{"id":2,"name":"B","desc":"y"}]')
        ->dataObjects(TestDataObject::class);

    expect($objects)->toHaveCount(2)
        ->and($objects[1])->toBeInstanceOf(TestDataObject::class)
        ->name->toBe('B');
});

it('can be read more than once even when the body cannot rewind', function () {
    $response = new Response(new Psr7Response(200, [], new NonSeekableStream('{"id":1,"name":"A","desc":"x"}')));

    expect($response->json())->toBe(['id' => 1, 'name' => 'A', 'desc' => 'x'])
        ->and($response->json())->toBe(['id' => 1, 'name' => 'A', 'desc' => 'x'])
        ->and($response->body())->toBe('{"id":1,"name":"A","desc":"x"}')
        ->and($response->body())->toBe('{"id":1,"name":"A","desc":"x"}');
});

it('can build a data object and still read the body afterwards', function () {
    $response = new Response(new Psr7Response(200, [], new NonSeekableStream('{"id":7,"name":"A","desc":"x"}')));

    expect($response->dataObject(TestDataObject::class)->id)->toBe(7)
        ->and($response->json()['id'])->toBe(7);
});

it('reads a data object out of an envelope', function () {
    $object = response(body: '{"data":{"id":1,"name":"Test","desc":"A card."}}')
        ->dataObject(TestDataObject::class, 'data');

    expect($object->id)->toBe(1);
});

it('reads a list out of an envelope', function () {
    $objects = response(body: '{"data":[{"id":1,"name":"A","desc":"x"},{"id":2,"name":"B","desc":"y"}],"meta":{"total":2}}')
        ->dataObjects(TestDataObject::class, 'data');

    expect($objects)->toHaveCount(2)
        ->and($objects[1]->name)->toBe('B');
});

it('says which keys are there when the one you asked for is not', function () {
    expect(fn() => response(body: '{"result":[],"meta":{}}')->dataObjects(TestDataObject::class, 'data'))
        ->toThrow(UnableToReadResponse::class, 'The API response has no [data] to read. It contains: result, meta.');
});

it('builds a page of results with its meta', function () {
    $page = response(body: '{
        "data": [{"id":1,"name":"A","desc":"x"},{"id":2,"name":"B","desc":"y"}],
        "links": {"first":"...","last":"...","prev":null,"next":"...?page=2"},
        "meta": {"current_page":1,"from":1,"last_page":5,"path":"/orders","per_page":2,"to":2,"total":10}
    }')->paginated(TestDataObject::class);

    expect($page)->toHaveCount(2)
        ->and($page->items[0])->toBeInstanceOf(TestDataObject::class)
        ->and($page->meta->currentPage)->toBe(1)
        ->and($page->meta->perPage)->toBe(2)
        ->and($page->meta->lastPage)->toBe(5)
        ->and($page->meta->total)->toBe(10)
        ->and($page->meta->hasMorePages())->toBeTrue()
        ->and($page->isEmpty())->toBeFalse();
});

it('can be iterated a page at a time', function () {
    $page = response(body: '{"data":[{"id":1,"name":"A","desc":"x"}],"meta":{"total":1}}')
        ->paginated(TestDataObject::class);

    $ids = [];

    foreach ($page as $object) {
        $ids[] = $object->id;
    }

    expect($ids)->toBe([1]);
});

it('copes with a page whose meta says nothing useful', function () {
    $page = response(body: '{"data":[],"meta":{}}')->paginated(TestDataObject::class);

    expect($page->isEmpty())->toBeTrue()
        ->and($page->meta->total)->toBeNull()
        ->and($page->meta->hasMorePages())->toBeNull();
});

it('knows it is on the last page', function () {
    $page = response(body: '{"data":[],"meta":{"current_page":5,"last_page":5}}')
        ->paginated(TestDataObject::class);

    expect($page->meta->hasMorePages())->toBeFalse();
});

it('falls back to from and to when there are no page numbers', function () {
    $page = response(body: '{"data":[],"meta":{"from":1,"to":25,"total":120}}')
        ->paginated(TestDataObject::class);

    expect($page->meta->hasMorePages())->toBeTrue();
});
