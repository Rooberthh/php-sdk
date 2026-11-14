<?php

declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Connector;
use Rooberthh\Sdk\Contracts\RequestContract;

/**
 * @param array<string, string> $headers
 * @param ?RequestContract<mixed>        $request
 *
 * @return RequestInterface the request as it went over the wire
 */
function sendWith(array $headers, ?RequestContract $request = null): RequestInterface
{
    $http = mockHttp();
    $http->addResponse(new Response(200));

    new Connector('https://api.example.com', headers: $headers, http: $http)->send($request ?? request());

    return $http->getLastRequest();
}

it('sends the default headers', function (): void {
    $sent = sendWith([]);

    expect($sent->getHeaderLine('Accept'))->toBe('application/json')
        ->and($sent->getHeaderLine('User-Agent'))->toStartWith('php-sdk/');
});

it('sends the Consumer headers with every request', function (): void {
    expect(sendWith(['X-Tenant' => 'acme'])->getHeaderLine('X-Tenant'))->toBe('acme');
});

it('lets a Consumer header replace a default of the same name', function (): void {
    $sent = sendWith(['Accept' => 'application/xml', 'User-Agent' => 'my-app/1.0']);

    expect($sent->getHeaderLine('Accept'))->toBe('application/xml')
        ->and($sent->getHeaderLine('User-Agent'))->toBe('my-app/1.0');
});
