<?php

namespace Rooberthh\Sdk\Tests;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Tests\Stubs\MockSDK;

abstract class SdkTestCase extends BaseTestCase
{
    public function createClient(): Client
    {
        return new MockSDK(
            url: 'https://api.mock.com',
        );
    }

    /**
     * @param string $fixture
     * @throws JsonException
     */
    protected function createMockClient(string $fixture): ClientContract
    {
        $mockClient = (new MockClient());

        $mockClient->addResponse(
            response: new Response(
                status: 200,
                headers: [],
                body: json_encode(
                    $this->fixture($fixture),
                ),
            ),
        );

        return (new MockSDK(
            url: 'https://api.mock.com',
        ))->client(
            client: $mockClient,
        );
    }

    protected function fixture(string $path): array
    {
        $filename = __DIR__ . "/Fixtures/{$path}.json";

        if (! file_exists(filename: $filename)) {
            throw new InvalidArgumentException('Failed to fetch fixture.');
        }

        return json_decode(
            json: file_get_contents(
                filename: $filename,
            ),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
