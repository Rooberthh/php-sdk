<?php

declare(strict_types=1);

use Rooberthh\Sdk\Exceptions\SdkException;
use Rooberthh\Sdk\Exceptions\UnableToHydrateResponse;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\NestedDataObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\PriceObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

it('hydrates snake_case responses onto camelCase properties', function () {
    $object = NestedDataObject::make([
        'id'          => 1,
        'name'        => 'Elemental HERO',
        'desc'        => 'A card.',
        'card_prices' => [
            'cardmarket_price' => '0.42',
            'tcgplayer_price'  => '0.48',
            'amazon_price'     => '0.77',
        ],
    ]);

    expect($object->cardPrices)->toBeInstanceOf(PriceObject::class)
        ->cardmarketPrice->toBe('0.42');
});

it('ignores response fields the object does not declare', function () {
    $object = TestDataObject::make([
        'id'          => 1,
        'name'        => 'Test',
        'desc'        => 'A card.',
        'brand_new'   => 'a field the API added yesterday',
    ]);

    expect($object->name)->toBe('Test');
});

it('throws when the response does not match the object', function () {
    TestDataObject::make(['id' => 'not-an-int', 'name' => 'Test']);
})->throws(UnableToHydrateResponse::class);

it('reports the path of every field that failed to hydrate', function () {
    $exception = null;

    try {
        TestDataObject::make(['id' => 'not-an-int', 'name' => 'Test']);
    } catch (UnableToHydrateResponse $exception) {
        //
    }

    expect($exception)->toBeInstanceOf(SdkException::class)
        ->and($exception->class)->toBe(TestDataObject::class)
        ->and($exception->errors)->toHaveCount(2)
        ->and(implode("\n", $exception->errors))
        ->toContain('id:')
        ->toContain('desc:');
});
