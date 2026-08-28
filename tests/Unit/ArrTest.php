<?php

declare(strict_types=1);

use Rooberthh\Sdk\Support\Arr;

it('returns the whole array when no key is named', function () {
    expect(Arr::get(['id' => 1], null))->toBe(['id' => 1]);
});

it('reads a top level key', function () {
    expect(Arr::get(['id' => 1, 'name' => 'Test'], 'name'))->toBe('Test');
});

it('walks a dot path', function () {
    expect(Arr::get(['meta' => ['total' => 9]], 'meta.total'))->toBe(9);
});

it('steps into a list by position', function () {
    $body = ['data' => [['id' => 1], ['id' => 2]]];

    expect(Arr::get($body, 'data.1.id'))->toBe(2);
});

it('gives back the default for a missing key', function () {
    expect(Arr::get(['id' => 1], 'nope'))->toBeNull()
        ->and(Arr::get(['id' => 1], 'nope', 'fallback'))->toBe('fallback');
});

it('gives back the default when a step of the path is not an array', function () {
    expect(Arr::get(['meta' => ['total' => 9]], 'meta.total.deeper', 'fallback'))
        ->toBe('fallback');
});

it('gives back the default when a path runs past the end of the body', function () {
    expect(Arr::get(['meta' => ['total' => 9]], 'meta.missing'))->toBeNull();
});

it('prefers a key that is literally there over the path it spells', function () {
    $body = ['meta.total' => 1, 'meta' => ['total' => 2]];

    expect(Arr::get($body, 'meta.total'))->toBe(1);
});

it('returns a null the array actually holds rather than the default', function () {
    expect(Arr::get(['next' => null], 'next', 'fallback'))->toBeNull();
});

it('reads an integer key without splitting it', function () {
    expect(Arr::get([10, 20], 1))->toBe(20);
});

it('gives back the default for an integer key that is not there', function () {
    expect(Arr::get([10, 20], 7, 'fallback'))->toBe('fallback');
});
