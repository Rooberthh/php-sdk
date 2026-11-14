<?php

declare(strict_types=1);

use Rooberthh\Sdk\Http\Uri;

it('parses the parts of an absolute URI', function (): void {
    $uri = Uri::parse('https://user@api.example.com:8443/v2/emails?limit=10#top');

    expect($uri)->not->toBeNull()
        ->and($uri->getScheme())->toBe('https')
        ->and($uri->getUserInfo())->toBe('user')
        ->and($uri->getHost())->toBe('api.example.com')
        ->and($uri->getPort())->toBe(8443)
        ->and($uri->getPath())->toBe('/v2/emails')
        ->and($uri->getQuery())->toBe('limit=10')
        ->and($uri->getFragment())->toBe('top');
});

it('normalises the scheme and host to lowercase', function (): void {
    expect(Uri::parse('HTTPS://API.Example.COM/Path')?->toString())->toBe('https://api.example.com/Path');
});

it('returns null for something that is not RFC 3986', function (string $uri): void {
    expect(Uri::parse($uri))->toBeNull();
})->with(['a space' => 'not a uri', 'a stray percent' => 'https://example.com/%zz']);

it('resolves a reference against a base', function (): void {
    $base = Uri::parse('https://api.example.com/v2/');

    expect(Uri::parse('emails/1', $base)?->toString())->toBe('https://api.example.com/v2/emails/1');
});

it('is immutable', function (): void {
    $uri = Uri::parse('https://api.example.com/v2');
    $changed = $uri?->withPath('/v3')->withQuery('a=1');

    expect($uri?->toString())->toBe('https://api.example.com/v2')
        ->and($changed?->toString())->toBe('https://api.example.com/v3?a=1');
});

it('compares by value', function (): void {
    expect(Uri::parse('https://example.com/a')?->equals(Uri::parse('HTTPS://EXAMPLE.com/a')))->toBeTrue()
        ->and(Uri::parse('https://example.com/a')?->equals(Uri::parse('https://example.com/b')))->toBeFalse();
});

it('casts to its string form', function (): void {
    expect((string) Uri::parse('https://example.com/a'))->toBe('https://example.com/a');
});
