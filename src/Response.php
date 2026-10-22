<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use Closure;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Exceptions\UnableToReadResponse;
use Rooberthh\Sdk\Pagination\PageMeta;
use Rooberthh\Sdk\Support\Arr;

/**
 * What the client hands back from a request.
 *
 * This wraps the PSR-7 response rather than replacing it — {@see self::psr()}
 * gives the original back, untouched. A failed request is reported by
 * {@see self::failed()}, not thrown, unless you ask for it to be thrown
 * ({@see self::throw()}) or ask for a Data Object ({@see self::dataObject()},
 * {@see self::dataObjects()}, {@see self::paginated()}), which only a
 * successful response can provide.
 */
final class Response
{
    private ?string $body = null;

    /** @var null|array<mixed, mixed> */
    private ?array $decoded = null;

    /**
     * @param ResponseInterface                   $response
     * @param null|Closure(Response): ?Throwable $exceptionFor maps a failed
     *        response to the exception to throw, or returns null to fall back
     *        to {@see RequestFailed}. Consulted only for a failed response.
     */
    public function __construct(
        private readonly ResponseInterface $response,
        private readonly ?Closure $exceptionFor = null,
    ) {}

    /**
     * The underlying PSR-7 response, for anything this class does not cover.
     */
    public function psr(): ResponseInterface
    {
        return $this->response;
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * The raw body, read once and kept — a response body is not always
     * rewindable, so reading it twice would otherwise come back empty.
     */
    public function body(): string
    {
        return $this->body ??= (string) $this->response->getBody();
    }

    /**
     * The decoded JSON body, or one part of it.
     *
     * Name a key to reach inside an envelope — `json('data')` for the rows,
     * `json('meta.total')` for something nested, `json('data.0.id')` to step
     * into a list. A key that is not there gives back the default rather than
     * throwing, because looking is not the same as asserting: reach for
     * {@see self::dataObject()} when the shape is a requirement rather than a guess.
     *
     * A key is tried whole before it is split, so an API that really does send
     * a "meta.total" key is still readable.
     *
     * @param  null|int|string $key
     * @param  mixed           $default
     *
     * @throws UnableToReadResponse when the body is not a JSON object or array
     * @return ($key is null ? array<mixed, mixed> : mixed)
     */
    public function json(string|int|null $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->decoded(), $key, $default);
    }

    /**
     * The body decoded once and kept.
     *
     * A response body is not always rewindable, so a second read of the stream
     * would come back empty. Internal callers use this rather than
     * {@see self::json()} so their array-ness is provable regardless of how a
     * conditional return type resolves.
     *
     * @throws UnableToReadResponse when the body is not a JSON object or array
     * @return array<mixed, mixed>
     */
    private function decoded(): array
    {
        if ($this->decoded !== null) {
            return $this->decoded;
        }

        try {
            $decoded = json_decode($this->body(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw UnableToReadResponse::notJson($this, $exception);
        }

        if (!is_array($decoded)) {
            throw UnableToReadResponse::notJson($this);
        }

        return $this->decoded = $decoded;
    }

    public function header(string $name): ?string
    {
        return $this->response->hasHeader($name)
            ? $this->response->getHeaderLine($name)
            : null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * Throw if the request failed. Opt-in: nothing throws unless you ask.
     *
     * The exception is the client's own when its `exceptionFor` hook maps this
     * response to one; otherwise it is {@see RequestFailed}.
     *
     * @throws Throwable
     */
    public function throw(): self
    {
        if ($this->failed()) {
            throw $this->failure();
        }

        return $this;
    }

    /**
     * The exception for a failed response: the one the client's `exceptionFor`
     * hook returns, or {@see RequestFailed} when there is no hook or it hands
     * back null.
     */
    private function failure(): Throwable
    {
        $mapped = $this->exceptionFor !== null
            ? ($this->exceptionFor)($this)
            : null;

        return $mapped ?? RequestFailed::with($this);
    }

    /**
     * Build a data object from the body.
     *
     * Pass a key for APIs that wrap their responses — `dataObject(Order::class,
     * 'data')` reads `{"data": {...}}`.
     *
     * Asking for a Data Object asserts the request succeeded: a failed response
     * is thrown ({@see self::throw()}) before the body is read, so the error is
     * the failed status rather than whatever the error body failed to map to.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param null|int|string $key
     *
     * @throws Throwable when the response did not come back 2xx
     * @return T
     */
    public function dataObject(string $class, string|int|null $key = null): DataObjectContract
    {
        $this->throw();

        return $class::make($this->section($key));
    }

    /**
     * Build a data object per entry of a list body.
     *
     * Pass a key for APIs that wrap their lists — `dataObjects(Order::class,
     * 'data')` reads the rows out of `{"data": [...], "meta": {...}}`. Without
     * one, the body itself must be the list.
     *
     * Asking for Data Objects asserts the request succeeded: a failed response
     * is thrown ({@see self::throw()}) before the body is read.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param null|int|string $key
     *
     * @throws Throwable when the response did not come back 2xx
     * @return list<T>
     */
    public function dataObjects(string $class, string|int|null $key = null): array
    {
        $this->throw();

        return array_map(
            static fn(mixed $data): DataObjectContract => $class::make((array) $data),
            array_values($this->section($key)),
        );
    }

    /**
     * One page of results, with whatever the API said about where it sits.
     *
     * Asking for a page asserts the request succeeded: a failed response is
     * thrown ({@see self::throw()}) before the body is read.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param string $dataKey
     * @param string $metaKey
     *
     * @throws Throwable when the response did not come back 2xx
     * @return PaginatedResponse<T>
     */
    public function paginated(
        string $class,
        string $dataKey = 'data',
        string $metaKey = 'meta',
    ): PaginatedResponse {
        $this->throw();

        $meta = $this->json($metaKey);

        return new PaginatedResponse(
            items: $this->dataObjects($class, $dataKey),
            meta: PageMeta::make(is_array($meta) ? $meta : []),
        );
    }

    /**
     * The part of the body a caller asked for, or the whole body when they
     * did not name one.
     *
     * Unlike {@see self::json()}, a key that leads nowhere is an error rather
     * than a default: naming a key and a class asserts a shape, and there is
     * no meaningful object to hand back when it is not there.
     *
     * @param null|int|string $key
     * @return array<mixed, mixed>
     */
    private function section(string|int|null $key): array
    {
        $body = $this->decoded();

        if ($key === null) {
            return $body;
        }

        $section = Arr::get($body, $key);

        if (!is_array($section)) {
            throw UnableToReadResponse::missingKey($this, $key);
        }

        return $section;
    }
}
