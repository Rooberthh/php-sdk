<?php

declare(strict_types=1);

use Http\Client\Exception\TransferException;
use Http\Mock\Client as MockClient;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Http\Method;
use Rooberthh\Sdk\Contracts\RequestContract;

/**
 * A mock Transport that fails the test on a request with no response queued,
 * rather than answering the empty 200 it would by default.
 */
function mockHttp(): MockClient
{
    $http = new MockClient();
    $http->setDefaultException(new TransferException('No response queued for this request.'));

    return $http;
}

/**
 * A Request to the given method and path whose result is the response itself,
 * for testing the Connector without writing a class per case.
 *
 * @param Method $method
 * @param string $path
 *
 * @return RequestContract<ResponseInterface>
 */
function request(Method $method = Method::GET, string $path = 'emails'): RequestContract
{
    return new readonly class ($method, $path) implements RequestContract {
        public function __construct(
            private Method $method,
            private string $path,
        ) {}

        public function method(): Method
        {
            return $this->method;
        }

        public function path(): string
        {
            return $this->path;
        }

        public function result(ResponseInterface $response): ResponseInterface
        {
            return $response;
        }
    };
}
