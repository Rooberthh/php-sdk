<?php

declare(strict_types=1);

use Rooberthh\Sdk\Exceptions\UnsafeUri;
use Rooberthh\Sdk\Http\BaseUri;

it('appends request paths to the base path', function (string $base, string $path, string $expected): void {
    expect(new BaseUri($base)->resolve($path)->toString())->toBe($expected);
})->with([
    'no base path'                  => ['https://api.example.com', 'emails', 'https://api.example.com/emails'],
    'base path without slash'       => ['https://api.example.com/v2', 'emails', 'https://api.example.com/v2/emails'],
    'base path with slash'          => ['https://api.example.com/v2/', 'emails', 'https://api.example.com/v2/emails'],
    'leading slash keeps base path' => ['https://api.example.com/v2', '/emails/1', 'https://api.example.com/v2/emails/1'],
    'empty path is the base'        => ['https://api.example.com/v2', '', 'https://api.example.com/v2/'],
    'port is kept'                  => ['http://localhost:8080/api', 'users', 'http://localhost:8080/api/users'],
]);

it('normalises the base', function (): void {
    expect(new BaseUri('HTTPS://API.Example.com/v2')->toString())->toBe('https://api.example.com/v2/')
        ->and(new BaseUri('https://api.example.com/v2')->scheme())->toBe('https');
});

it('refuses a base that is not safe', function (string $base): void {
    new BaseUri($base);
})->with([
    'relative'     => '/v2',
    'no host'      => 'https://',
    'invalid'      => 'not a uri',
    'query'        => 'https://api.example.com/v2?key=1',
    'fragment'     => 'https://api.example.com/v2#top',
    'credentials'  => 'https://user:pass@api.example.com',
])->throws(UnsafeUri::class);

it('refuses a path that would leave the base', function (string $path): void {
    new BaseUri('https://api.example.com/v2')->resolve($path);
})->with([
    'absolute URL'         => 'https://evil.example.com/steal',
    'scheme-relative'      => '//evil.example.com/steal',
    'other scheme'         => 'javascript:alert(1)',
    'dot segments'         => 'emails/../../admin',
    'encoded dot segments' => 'emails/%2e%2e/%2E%2E/admin',
    'query'                => 'emails?limit=1',
    'fragment'             => 'emails#top',
    'invalid'              => 'emails/a b',
])->throws(UnsafeUri::class);

it('allows absolute URIs under the base, such as next links', function (): void {
    $base = new BaseUri('https://api.example.com/v2');

    expect($base->allows('https://api.example.com/v2/emails?after=abc'))->toBeTrue()
        ->and($base->allows('https://API.example.com/v2/emails'))->toBeTrue()
        ->and($base->allows('https://api.example.com/v3/emails'))->toBeFalse()
        ->and($base->allows('http://api.example.com/v2/emails'))->toBeFalse()
        ->and($base->allows('https://api.example.com:8443/v2/emails'))->toBeFalse()
        ->and($base->allows('https://user@api.example.com/v2/emails'))->toBeFalse()
        ->and($base->allows('https://evil.example.com/v2/emails'))->toBeFalse()
        ->and($base->allows('not a uri'))->toBeFalse();
});
