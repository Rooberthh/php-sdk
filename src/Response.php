<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Exceptions\RequestFailed;
use Rooberthh\Sdk\Exceptions\UnableToReadResponse;

/**
 * What the client hands back from a request.
 *
 * This wraps the PSR-7 response rather than replacing it — {@see self::psr()}
 * gives the original back, untouched. Nothing here decides anything on your
 * behalf: a failed request is reported by {@see self::failed()}, not thrown,
 * and data objects are built only if you ask for them.
 */
final readonly class Response
{
    public function __construct(
        private ResponseInterface $response,
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

    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    /**
     * The decoded JSON body.
     *
     *
     * @throws UnableToReadResponse when the body is not a JSON object or array
     * @return array<mixed, mixed>
     */
    public function json(): array
    {
        try {
            $decoded = json_decode($this->body(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw UnableToReadResponse::notJson($this, $exception);
        }

        if (!is_array($decoded)) {
            throw UnableToReadResponse::notJson($this);
        }

        return $decoded;
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
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @return T
     */
    public function dto(string $class): DataObjectContract
    {
        return $class::make($this->json());
    }

    /**
     * Build a data object per entry of a list body.
     *
     * @template T of DataObjectContract
     *
     * @param  class-string<T> $class
     * @return list<T>
     */
    public function dtos(string $class): array
    {
        return array_map(
            static fn(mixed $data): DataObjectContract => $class::make((array) $data),
            array_values($this->json()),
        );
    }
}
