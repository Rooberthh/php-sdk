<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Resources;

use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;
use Rooberthh\Sdk\FilterBuilder;
use Rooberthh\Sdk\Pagination\OffsetPaginator;
use Rooberthh\Sdk\Pagination\PagedPaginator;
use Rooberthh\Sdk\Response;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\NestedDataObject;
use Rooberthh\Sdk\Tests\Stubs\DataObjects\TestDataObject;

class TestResource
{
    use CanAccessClient;
    use CanCreateRequests;

    /**
     * @return list<TestDataObject>
     */
    public function list(): array
    {
        $request = FilterBuilder::for($this->request(Method::GET, '/test'))
            ->where('name', 'asc')
            ->apply();

        return $this->client->send($request)->dtos(TestDataObject::class);
    }

    /**
     * @return list<NestedDataObject>
     */
    public function listNested(): array
    {
        return $this->client
            ->send($this->request(Method::GET, '/test'))
            ->dtos(NestedDataObject::class);
    }

    /**
     * @param int $perPage
     * @return PagedPaginator<TestDataObject>
     */
    public function paged(int $perPage = 2): PagedPaginator
    {
        return new PagedPaginator(
            $this->client,
            $this->request(Method::GET, '/test'),
            TestDataObject::class,
            perPage: $perPage,
        );
    }

    /**
     * @param int $limit
     * @return OffsetPaginator<TestDataObject>
     */
    public function offset(int $limit = 2): OffsetPaginator
    {
        return new OffsetPaginator(
            $this->client,
            $this->request(Method::GET, '/test'),
            TestDataObject::class,
            limit: $limit,
            dataKey: 'data',
        );
    }

    public function store(object $payload): Response
    {
        $request = $this->request(Method::POST, '/test')
            ->withBody($this->payload($payload));

        return $this->client->send($request);
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function storeJson(array $data): Response
    {
        $request = $this->request(Method::POST, '/test')
            ->withBody($this->json($data));

        return $this->client->send($request);
    }

    public function storeRaw(string $body): Response
    {
        $request = $this->request(Method::POST, '/test')
            ->withBody($this->body($body));

        return $this->client->send($request);
    }
}
