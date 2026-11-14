<?php

declare(strict_types=1);

use Rooberthh\Sdk\Exceptions\InvalidQuery;
use Rooberthh\Sdk\Http\Query;

it('encodes the parameters in the order given', function () {
    expect((new Query(['status' => 'active', 'page' => 2, 'ratio' => 1.5]))->toString())
        ->toBe('status=active&page=2&ratio=1.5');
});

it('leaves out null values', function () {
    expect((new Query(['status' => null, 'page' => 2]))->toString())->toBe('page=2');
});

it('sends booleans as true and false', function () {
    expect((new Query(['archived' => true, 'draft' => false]))->toString())->toBe('archived=true&draft=false');
});

it('percent-encodes keys and values per RFC 3986', function () {
    expect((new Query(['q' => 'a b&c=d#e/ü', 'sort by' => 'name']))->toString())
        ->toBe('q=a%20b%26c%3Dd%23e%2F%C3%BC&sort%20by=name');
});

it('repeats the key for each value in a list, dropping nulls', function () {
    expect((new Query(['tag' => ['a', null, 'b']]))->toString())->toBe('tag=a&tag=b');
});

it('lets the key choose the bracket style for lists', function () {
    expect((new Query(['tags[]' => ['a', 'b']]))->toString())->toBe('tags%5B%5D=a&tags%5B%5D=b');
});

it('is empty when every value is null or an empty list', function () {
    $query = new Query(['status' => null, 'tags' => []]);

    expect($query->isEmpty())->toBeTrue()
        ->and($query->toString())->toBe('');
});

it('refuses an array that is not a list', function () {
    new Query(['filter' => ['status' => 'active']]);
})->throws(InvalidQuery::class, 'filter[key]');
