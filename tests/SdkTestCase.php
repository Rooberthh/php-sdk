<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

abstract class SdkTestCase extends BaseTestCase
{
    public function createClient(): Client
    {
        return new MockClient(
            url: 'https://api.mock.com',
            builder: (new FakeTransport())->builder(),
        );
    }

    /**
     * @param string $fixture
     * @throws JsonException
     */
    protected function createMockClient(string $fixture): ClientContract
    {
        return new MockClient(
            url: 'https://api.mock.com',
            builder: (new FakeTransport(['*' => $this->fixture($fixture)]))->builder(),
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
