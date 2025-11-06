<?php

namespace Rooberthh\Sdk\Tests\Stubs\Resources;

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;
use Rooberthh\Sdk\Filter;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\NestedDataObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

class TestResource
{
    use CanAccessClient;
    use CanCreateRequests;

    public function list()
    {
        $request = $this->request(Method::GET, '/test');

        $this->applyFilters(
            $request,
            [
                Filter::make('name', 'asc'),
            ],
        );

        $response = $this->client->send($request);

        return array_map(function ($data) {
            return TestDataObject::make($data);
        }, json_decode($response->getBody()->getContents(), true));
    }

    public function listNested()
    {
        $request = $this->request(Method::GET, '/test');

        $response = $this->client->send($request);

        return array_map(function ($data) {
            return NestedDataObject::make($data);
        }, json_decode($response->getBody()->getContents(), true));
    }

    public function store($payload)
    {
        $request = $this->request(Method::POST, '/test')
            ->withBody($this->payload(json_encode($payload)));

        return $this->client->send($request);
    }
}
