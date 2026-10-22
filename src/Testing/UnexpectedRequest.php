<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Testing;

use LogicException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Exceptions\SdkException;

/**
 * Thrown by {@see FakeTransport} when a request matches none of the registered
 * fakes. A missing fake is a mistake in the test, not a condition the SDK
 * should handle, so this is a {@see LogicException}. It is also a
 * {@see ClientExceptionInterface} so it surfaces where a real transport failure
 * would, and an {@see SdkException} so it catches with everything else the SDK
 * throws.
 */
final class UnexpectedRequest extends LogicException implements SdkException, ClientExceptionInterface
{
    /**
     * @param list<string> $keys the fakes that were registered, for the message
     * @param RequestInterface $request
     */
    public static function for(RequestInterface $request, array $keys): self
    {
        $registered = $keys === []
            ? '  (none)'
            : implode("\n", array_map(static fn(string $key): string => "  - {$key}", $keys));

        return new self(sprintf(
            "No fake matched %s %s.\nRegistered keys:\n%s",
            $request->getMethod(),
            (string) $request->getUri(),
            $registered,
        ));
    }
}
