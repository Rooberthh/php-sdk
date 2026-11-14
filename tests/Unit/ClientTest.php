<?php

declare(strict_types=1);

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Exceptions\UnsafeUri;

function client(string $url = 'https://api.example.com/v2'): \Rooberthh\Sdk\Connector
{
    return new class ($url) extends \Rooberthh\Sdk\Connector {};
}

it('resolves each request path under the base URI', function () {
    $request = client()->createRequest(Method::GET, '/emails/1');

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toBe('https://api.example.com/v2/emails/1');
});

it('refuses an unsafe base URI', function () {
    client('https://user:pass@api.example.com');
})->throws(UnsafeUri::class);

it('refuses a path that leaves the base URI', function () {
    client()->createRequest(Method::GET, 'https://evil.example.com');
})->throws(UnsafeUri::class);
