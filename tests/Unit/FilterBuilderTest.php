<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Rooberthh\Sdk\FilterBuilder;

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
        'page'   => '1',
    ]);
});

it('applies callback when condition is true', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('status', 'active')
        ->when(true, fn($b) => $b->where('archived', 'true'));

    expect($builder->getFilters())->toBe([
        'status'   => 'active',
        'archived' => 'true',
    ]);
});

it('skips callback when condition is false', function () {
    $builder = FilterBuilder::for($this->request)
        ->where('status', 'active')
        ->when(false, fn($b) => $b->where('archived', 'true'));

    expect($builder->getFilters())->toBe([
        'status' => 'active',
    ]);
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
