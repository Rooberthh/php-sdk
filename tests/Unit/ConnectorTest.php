<?php

declare(strict_types=1);

use Http\Message\Authentication\Bearer;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Connector;
use Rooberthh\Sdk\Exceptions\UnsafeUri;
use Rooberthh\Sdk\Contracts\BodyContract;
use Rooberthh\Sdk\Http\Body\FormBody;
use Rooberthh\Sdk\Http\Body\JsonBody;
use Rooberthh\Sdk\Contracts\HasBody;
use Rooberthh\Sdk\Contracts\RequestContract;

/**
 * A POST to `emails` that sends the given Body.
 *
 * @param BodyContract $body
 *
 * @return RequestContract<ResponseInterface>
 */
function requestWithBody(BodyContract $body): RequestContract
{
    return new readonly class ($body) implements HasBody, RequestContract {
        public function __construct(
            private BodyContract $body,
        ) {}

        public function method(): Method
        {
            return Method::POST;
        }

        public function path(): string
        {
            return 'emails';
        }

        public function body(): BodyContract
        {
            return $this->body;
        }

        public function result(ResponseInterface $response): ResponseInterface
        {
            return $response;
        }
    };
}

beforeEach(function () {
    $this->http = mockHttp();
    $this->connector = new Connector('https://api.example.com/v2', new Bearer('connector-token'), http: $this->http);
});

it('sends the Request method to its path under the base URI, with no body', function () {
    $this->http->addResponse(new Response(200));

    $this->connector->send(request(Method::PUT, '/emails/1'));

    $sent = $this->http->getLastRequest();

    expect($sent->getMethod())->toBe('PUT')
        ->and((string) $sent->getUri())->toBe('https://api.example.com/v2/emails/1')
        ->and((string) $sent->getBody())->toBe('');
});

it('sends the Body of a Request that has one, with its Content-Type', function () {
    $this->http->addResponse(new Response(200));

    $this->connector->send(requestWithBody(new FormBody(['name' => 'Acme'])));

    $sent = $this->http->getLastRequest();

    expect((string) $sent->getBody())->toBe('name=Acme')
        ->and($sent->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded');
});

it('sends no Content-Type with a Request that has no Body', function () {
    $this->http->addResponse(new Response(200));

    $this->connector->send(request());

    expect($this->http->getLastRequest()->hasHeader('Content-Type'))->toBeFalse();
});

it("keeps the Body's Content-Type over a Consumer header of the same name", function () {
    $http = mockHttp();
    $http->addResponse(new Response(200));
    $connector = new Connector('https://api.example.com', headers: ['Content-Type' => 'text/plain'], http: $http);

    $connector->send(requestWithBody(new JsonBody(['name' => 'Acme'])));

    expect($http->getLastRequest()->getHeaderLine('Content-Type'))->toBe('application/json');
});

it('refuses an unsafe base URI', function () {
    new Connector('https://user:pass@api.example.com');
})->throws(UnsafeUri::class);

it('refuses a Request path that leaves the base URI', function () {
    $this->connector->send(request(path: 'https://evil.example.com'));
})->throws(UnsafeUri::class);

it("authenticates with the Connector's strategy", function () {
    $this->http->addResponse(new Response(200));

    $this->connector->send(request());

    expect($this->http->getLastRequest()->getHeaderLine('Authorization'))->toBe('Bearer connector-token');
});

it('returns the Request result', function () {
    $this->http->addResponse(new Response(200, [], '{"id":1}'));

    $request = new readonly class implements RequestContract {
        public function method(): Method
        {
            return Method::GET;
        }

        public function path(): string
        {
            return 'emails/1';
        }

        public function result(ResponseInterface $response): int
        {
            return json_decode((string) $response->getBody(), true)['id'];
        }
    };

    expect($this->connector->send($request))->toBe(1);
});

it('returns the response itself from raw()', function () {
    $response = new Response(202);
    $this->http->addResponse($response);

    expect($this->connector->raw(request()))->toBe($response);
});

it('can send the same Request again', function () {
    $this->http->addResponse(new Response(200));
    $this->http->addResponse(new Response(200));
    $request = request(path: 'emails/1');

    $this->connector->send($request);
    $this->connector->send($request);

    [$first, $second] = $this->http->getRequests();

    expect((string) $second->getUri())->toBe((string) $first->getUri());
});
