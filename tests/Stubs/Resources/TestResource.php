<?php

namespace Rooberthh\Sdk\Tests\Stubs\Resources;

use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateDataObjects;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

class TestResource
{
    use CanAccessClient;
    use CanCreateDataObjects;
    use CanCreateRequests;

    public function list()
    {
        $request = $this->request('GET', '/test');

        $response = $this->client->send($request);

        return array_map(function ($data) {
            return TestDataObject::make($data);
        }, json_decode($response->getBody()->getContents(), true));
    }
}
