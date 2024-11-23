<?php

namespace Rooberthh\Sdk\Tests\Feature;

use Rooberthh\Sdk\Tests\SdkTestCase;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

uses(SdkTestCase::class);

it('can send return an array of data-objects', function () {
    $client = $this->createMockClient('list');

    $response = $client->tests()->list();

    foreach ($response as $data) {
        expect($data)->toBeInstanceOf(TestDataObject::class);
    }
});
