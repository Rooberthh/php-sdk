<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Resources;

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\NestedDataObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

class TestResource
{
    use CanAccessClient;

    /**
     * @return list<TestDataObject>
     */
    public function list(): array
    {
        return $this->client
            ->request(Method::GET, '/test', query: ['name' => 'asc'])
            ->dtos(TestDataObject::class);
    }

    /**
     * @return list<NestedDataObject>
     */
    public function listNested(): array
    {
        return $this->client
            ->request(Method::GET, '/test')
            ->dtos(NestedDataObject::class);
    }

    public function store(object $payload): Response
    {
        return $this->client->request(Method::POST, '/test', body: $payload);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function storeJson(array $data): Response
    {
        return $this->client->request(Method::POST, '/test', body: $data);
    }

    public function storeRaw(string $body): Response
    {
        return $this->client->request(Method::POST, '/test', body: $body);
    }
}
