<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Exceptions\UnableToReadResponse;
use Rooberthh\Sdk\Pagination\PageMeta;
use Rooberthh\Sdk\Support\Arr;

/**
 * What the client hands back from a request.
 *
 * This wraps the PSR-7 response rather than replacing it — {@see self::psr()}
 * gives the original back, untouched. Nothing here decides anything on your
 * behalf: a failed request is reported by {@see self::failed()}, not thrown,
 * and data objects are built only if you ask for them.
 */
final class Response
{
    private ?string $body = null;

    /** @var null|array<mixed, mixed> */
    private ?array $decoded = null;

    public function __construct(
        private readonly ResponseInterface $response,
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
     * {@see self::dto()} when the shape is a requirement rather than a guess.
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
     * @throws RequestFailed
     */
    public function throw(): self
    {
        if ($this->failed()) {
            throw RequestFailed::with($this);
        }

        return $this;
    }

    /**
     * Build a data object from the body.
     *
     * Pass a key for APIs that wrap their responses — `dto(Order::class,
     * 'data')` reads `{"data": {...}}`.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param null|int|string $key
     * @return T
     */
    public function dto(string $class, string|int|null $key = null): DataObjectContract
    {
        return $class::make($this->section($key));
    }

    /**
     * Build a data object per entry of a list body.
     *
     * Pass a key for APIs that wrap their lists — `dtos(Order::class, 'data')`
     * reads the rows out of `{"data": [...], "meta": {...}}`. Without one, the
     * body itself must be the list.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param null|int|string $key
     * @return list<T>
     */
    public function dtos(string $class, string|int|null $key = null): array
    {
        return array_map(
            static fn(mixed $data): DataObjectContract => $class::make((array) $data),
            array_values($this->section($key)),
        );
    }

    /**
     * One page of results, with whatever the API said about where it sits.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @param string $dataKey
     * @param string $metaKey
     * @return PaginatedResponse<T>
     */
    public function paginated(
        string $class,
        string $dataKey = 'data',
        string $metaKey = 'meta',
    ): PaginatedResponse {
        $meta = $this->json($metaKey);

        return new PaginatedResponse(
            items: $this->dtos($class, $dataKey),
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
