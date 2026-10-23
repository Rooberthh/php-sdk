<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs\Resources;

use Http\Message\Authentication;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Concerns\Resources\CanAccessClient;
use Rooberthh\Sdk\Concerns\Resources\CanCreateRequests;
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
        $request = $this->request(Method::GET, '/test', query: ['name' => 'asc']);

        return $this->client->send($request)->dataObjects(TestDataObject::class);
    }

    /**
     * @return list<NestedDataObject>
     */
    public function listNested(): array
    {
        return $this->client
            ->send($this->request(Method::GET, '/test'))
            ->dataObjects(NestedDataObject::class);
    }

    /**
     * @param array<mixed, mixed>|object|string $body
     */
    public function store(object|array|string $body): Response
    {
        $request = $this->request(Method::POST, '/test')
            ->withBody($this->payload($body));

        return $this->client->send($request);
    }

    public function status(?Authentication $auth = null): Response
    {
        return $this->client->send($this->request(Method::GET, '/status'), auth: $auth);
    }
}
