<?php

declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Connector;

/**
 * A PSR-18 client that records the request it was sent and answers 200.
 */
function recordingClient(): ClientInterface
{
    return new class implements ClientInterface {
        public ?RequestInterface $sent = null;

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $this->sent = $request;

            return new Response(200);
        }
    };
}

/**
 * @param array<string, string> $headers
 * @param ?RequestInterface $request
 * @return RequestInterface the request as it went over the wire
 */
function sendWith(array $headers, ?RequestInterface $request = null): RequestInterface
{
    $http = recordingClient();
    $connector = new Connector('https://api.example.com', headers: $headers, http: $http);

    $connector->send($request ?? $connector->createRequest(Method::GET, 'emails'));

    return $http->sent;
}

it('sends the default headers', function () {
    $sent = sendWith([]);

    expect($sent->getHeaderLine('Accept'))->toBe('application/json')
        ->and($sent->getHeaderLine('User-Agent'))->toStartWith('php-sdk/');
});

it('sends the Consumer headers with every request', function () {
    expect(sendWith(['X-Tenant' => 'acme'])->getHeaderLine('X-Tenant'))->toBe('acme');
});

it('lets a Consumer header replace a default of the same name', function () {
    $sent = sendWith(['Accept' => 'application/xml', 'User-Agent' => 'my-app/1.0']);

    expect($sent->getHeaderLine('Accept'))->toBe('application/xml')
        ->and($sent->getHeaderLine('User-Agent'))->toBe('my-app/1.0');
});

it('leaves a header the request sets itself alone', function () {
    $connector = new Connector('https://api.example.com');
    $request = $connector->createRequest(Method::GET, 'emails')->withHeader('Accept', 'text/csv');

    expect(sendWith(['Accept' => 'application/xml'], $request)->getHeaderLine('Accept'))->toBe('text/csv');
});
