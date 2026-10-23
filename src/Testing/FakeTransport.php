<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Testing;

use Closure;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A Transport that answers from a table of canned responses instead of the
 * network, so an SDK Author can test their SDK without wiring a mock of their
 * own.
 *
 * Keys are an optional HTTP method, a space, then a path pattern; a key with no
 * method matches any method, and methods compare case-insensitively. The
 * pattern is matched against the request URI's path with the leading `/`
 * stripped from both sides and the query string ignored: `*` matches any run of
 * characters, `/` included, and every other character is literal, so `*` alone
 * matches every request. The first matching key wins, in array order.
 *
 * Values are a {@see ResponseInterface}, an array (shorthand for
 * {@see FakeResponse::json()}), or a `Closure(RequestInterface): ResponseInterface`
 * for dynamic responses. A request that matches nothing throws
 * {@see UnexpectedRequest} rather than falling back to a default — a missing
 * fake is a bug in the test.
 *
 * It implements the PSR-18 client interface, so it is handed to a Client with
 * `->client($fake)` and sits at the far end of the plugin chain: recorded
 * requests are exactly what would have gone over the wire, auth and default
 * headers included.
 */
final class FakeTransport implements ClientInterface
{
    /** @var array<string, array<mixed, mixed>|Closure(RequestInterface): ResponseInterface|ResponseInterface> */
    private array $fakes;

    /** @var list<RequestInterface> */
    private array $recorded = [];

    /**
     * @param array<string, array<mixed, mixed>|Closure(RequestInterface): ResponseInterface|ResponseInterface> $fakes
     */
    public function __construct(array $fakes = [])
    {
        $this->fakes = $fakes;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->recorded[] = $request;

        foreach ($this->fakes as $key => $value) {
            if ($this->matches((string) $key, $request)) {
                return $this->resolve($value, $request);
            }
        }

        throw UnexpectedRequest::for($request, array_map(strval(...), array_keys($this->fakes)));
    }

    /**
     * Every request this transport was asked to send, in order.
     *
     * @return list<RequestInterface>
     */
    public function recorded(): array
    {
        return $this->recorded;
    }

    /**
     * At least one recorded request satisfies the callback.
     *
     * @param Closure(RequestInterface): bool $callback
     */
    public function assertSent(Closure $callback): void
    {
        Assert::assertTrue(
            $this->hasMatch($callback),
            $this->withRecorded('Expected a request matching the callback to have been sent.'),
        );
    }

    /**
     * No recorded request satisfies the callback.
     *
     * @param Closure(RequestInterface): bool $callback
     */
    public function assertNotSent(Closure $callback): void
    {
        Assert::assertFalse(
            $this->hasMatch($callback),
            $this->withRecorded('Expected no request matching the callback to have been sent.'),
        );
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->recorded,
            $this->withRecorded(sprintf('Expected %d request(s) to have been sent.', $count)),
        );
    }

    public function assertNothingSent(): void
    {
        Assert::assertCount(
            0,
            $this->recorded,
            $this->withRecorded('Expected no requests to have been sent.'),
        );
    }

    private function matches(string $key, RequestInterface $request): bool
    {
        [$method, $pattern] = $this->split($key);

        if ($method !== null && strtoupper($method) !== strtoupper($request->getMethod())) {
            return false;
        }

        $path = ltrim($request->getUri()->getPath(), '/');
        $pattern = ltrim($pattern, '/');

        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';

        return preg_match($regex, $path) === 1;
    }

    /**
     * Split a key into its optional method and its path pattern. A path never
     * contains a space, so the first space is the method separator.
     *
     * @param string $key
     * @return array{0: null|string, 1: string}
     */
    private function split(string $key): array
    {
        if (str_contains($key, ' ')) {
            [$method, $pattern] = explode(' ', $key, 2);

            return [$method, $pattern];
        }

        return [null, $key];
    }

    /**
     * @param array<mixed, mixed>|Closure(RequestInterface): ResponseInterface|ResponseInterface $value
     * @param RequestInterface $request
     */
    private function resolve(ResponseInterface|array|Closure $value, RequestInterface $request): ResponseInterface
    {
        return match (true) {
            $value instanceof Closure => $value($request),
            $value instanceof ResponseInterface => $value,
            default => FakeResponse::json($value),
        };
    }

    /**
     * @param Closure(RequestInterface): bool $callback
     */
    private function hasMatch(Closure $callback): bool
    {
        foreach ($this->recorded as $request) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Append the recorded requests to a failure message, one `METHOD URL` a line.
     * @param string $message
     */
    private function withRecorded(string $message): string
    {
        if ($this->recorded === []) {
            return $message . "\nNo requests were recorded.";
        }

        $lines = array_map(
            static fn(RequestInterface $request): string => $request->getMethod() . ' ' . (string) $request->getUri(),
            $this->recorded,
        );

        return $message . "\nRecorded requests:\n" . implode("\n", $lines);
    }
}
