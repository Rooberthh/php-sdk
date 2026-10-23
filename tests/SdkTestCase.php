<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Rooberthh\Sdk\Testing\FakeTransport;
use Rooberthh\Sdk\Tests\Stubs\MockClient;

abstract class SdkTestCase extends BaseTestCase
{
    public function createClient(): MockClient
    {
        return (new MockClient('https://api.mock.com'))->client(new FakeTransport());
    }

    /**
     * @param string $fixture
     * @throws JsonException
     */
    protected function createMockClient(string $fixture): MockClient
    {
        return (new MockClient('https://api.mock.com'))
            ->client(new FakeTransport(['*' => $this->fixture($fixture)]));
    }

    /**
     * @param string $path
     * @throws InvalidArgumentException|JsonException
     * @return array<mixed, mixed>
     */
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
