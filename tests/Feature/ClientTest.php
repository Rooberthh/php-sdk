<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Feature;

use Rooberthh\HttpTools\Enums\StatusCode;
use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\NestedDataObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\PriceObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

uses(SdkTestCase::class);

it('can return an array of data-objects', function () {
    $client = $this->createMockClient('list');

    $response = $client->tests()->list();

    foreach ($response as $data) {
        expect($data)
            ->toBeInstanceOf(TestDataObject::class)
            ->toHaveProperties(
                [
                    'id',
                    'name',
                    'desc',
                ],
            );
    }
});

it('can return an array of data-objects with nested data-objects', function () {
    $client = $this->createMockClient('list');

    $response = $client->tests()->listNested();

    foreach ($response as $data) {
        expect($data)->toBeInstanceOf(NestedDataObject::class)
            ->toHaveProperties(
                [
                    'id',
                    'name',
                    'desc',
                    'price',
                ],
            )
            ->and($data->price)->toBeInstanceOf(PriceObject::class)
            ->toHaveProperties(
                [
                    "cardmarket_price",
                    "tcgplayer_price",
                    "amazon_price",
                ],
            );
    }
});

it('can post a payload', function () {
    $client = $this->createMockClient('list-store');

    $response = $client->tests()->store(['test' => 'payload']);

    expect($response->getStatusCode())->toBe(StatusCode::OK->value);
});
