<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Rooberthh\Sdk\FilterBuilder;
use Rooberthh\Sdk\Tests\Stubs\Enums\Status;

beforeEach(function () {
    $this->factory = new Psr17Factory();
    $this->request = $this->factory->createRequest('GET', 'https://api.example.com/users');
});

it('can add multiple filters to a request', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('status', 'active')
        ->where('role', 'admin')
        ->where('page', 1);

    expect($builder->getFilters())->toBe([
        'status' => 'active',
        'role'   => 'admin',
        'page'   => 1,
    ]);
});

it('can add many filters at once', function () {
    $builder = FilterBuilder::for($this->request)
        ->whereAll(['status' => 'active', 'page' => 2]);

    expect($builder->toQueryString())->toBe('status=active&page=2');
});

it('applies filters to request query string', function () {
    $request = FilterBuilder::for($this->request)
        ->where('status', 'active')
        ->where('page', 2)
        ->apply();

    expect($request->getUri()->getQuery())->toBe('status=active&page=2');
});

it('returns filters array via getFilters', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('foo', 'bar');

    expect($builder->getFilters())
        ->toBeArray()
        ->toHaveKey('foo', 'bar');
});

it('keeps query parameters already present on the request', function () {
    $request = $this->factory->createRequest('GET', 'https://api.example.com/users?expand=all');

    $applied = FilterBuilder::for($request)
        ->where('status', 'active')
        ->apply();

    expect($applied->getUri()->getQuery())->toBe('expand=all&status=active');
});

it('overrides a query parameter already present on the request', function () {
    $request = $this->factory->createRequest('GET', 'https://api.example.com/users?page=1');

    $applied = FilterBuilder::for($request)
        ->where('page', 7)
        ->apply();

    expect($applied->getUri()->getQuery())->toBe('page=7');
});

it('merges bracketed keys against existing bracketed parameters', function () {
    $request = $this->factory->createRequest('GET', 'https://api.example.com/users?filter%5Brole%5D=admin');

    $applied = FilterBuilder::for($request)
        ->where('filter[status]', 'active')
        ->apply();

    parse_str($applied->getUri()->getQuery(), $params);

    expect($params)->toBe([
        'filter' => ['role' => 'admin', 'status' => 'active'],
    ]);
});

it('renders booleans as true and false', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('archived', true)
        ->where('recurring', false);

    expect($builder->toQueryString())->toBe('archived=true&recurring=false');
});

it('omits null filters entirely', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('status', 'active')
        ->where('role', null);

    expect($builder->toQueryString())->toBe('status=active');
});

it('renders backed enums as their value', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('status', Status::Active);

    expect($builder->toQueryString())->toBe('status=active');
});

it('renders dates in ATOM format', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('since', new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC')));

    expect(urldecode($builder->toQueryString()))->toBe('since=2026-01-02T03:04:05+00:00');
});

it('renders array filters as repeated bracketed parameters', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('tags', ['vip', 'new'])
        ->where('status', [Status::Active, Status::Archived]);

    parse_str($builder->toQueryString(), $params);

    expect($params)->toBe([
        'tags'   => ['vip', 'new'],
        'status' => ['active', 'archived'],
    ]);
});

it('encodes spaces per RFC 3986', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('name', 'jane doe');

    expect($builder->toQueryString())->toBe('name=jane%20doe');
});

it('throws when a filter cannot be converted to a query parameter', function () {
    FilterBuilder::for($this->request)
        ->where('client', new stdClass())
        ->toQueryString();
})->throws(InvalidArgumentException::class, 'Filter [client] cannot be converted');
