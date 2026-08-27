<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Pagination;

use Generator;
use IteratorAggregate;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Exceptions\PaginationFailed;
use Rooberthh\Sdk\Response;

/**
 * Walks a list endpoint one page at a time.
 *
 * Pages are fetched as you iterate, so a large result set costs one page of
 * memory rather than all of it. Nothing is requested until you start.
 *
 * @template T of DataObjectContract
 *
 * @implements IteratorAggregate<int, T>
 */
abstract class Paginator implements IteratorAggregate
{
    /**
     * @param class-string<T> $dataObject the object each entry is built into
     * @param null|string     $dataKey    key holding the entries, for enveloped
     *                                    responses like `{"data": [...]}`
     * @param int             $maxPages   stops a broken endpoint paging forever
     * @param ClientContract $client
     * @param RequestInterface $request
     */
    public function __construct(
        protected readonly ClientContract $client,
        protected readonly RequestInterface $request,
        protected readonly string $dataObject,
        protected readonly ?string $dataKey = null,
        protected readonly int $maxPages = 1000,
    ) {}

    /**
     * The request for the page after this one, or null when there isn't one.
     * @param RequestInterface $request
     * @param Response $response
     */
    abstract protected function next(RequestInterface $request, Response $response): ?RequestInterface;

    /**
     * Every page, as it arrives.
     *
     * @return Generator<int, Response>
     */
    public function pages(): Generator
    {
        $request = $this->request;
        $page = 0;

        while ($request instanceof RequestInterface) {
            if (++$page > $this->maxPages) {
                throw PaginationFailed::tooManyPages($this->maxPages);
            }

            $response = $this->client->send($request);

            yield $response;

            $request = $this->next($request, $response);
        }
    }

    /**
     * Every entry across every page.
     *
     * @return Generator<int, T>
     */
    public function getIterator(): Generator
    {
        foreach ($this->pages() as $response) {
            foreach ($this->entries($response) as $entry) {
                yield ($this->dataObject)::make((array) $entry);
            }
        }
    }

    /**
     * Every entry, fetched up front. Only for result sets you know are small.
     *
     * @return list<T>
     */
    public function all(): array
    {
        return iterator_to_array($this, preserve_keys: false);
    }

    /**
     * The raw entries on a page.
     *
     * @param Response $response
     * @return list<mixed>
     */
    protected function entries(Response $response): array
    {
        $body = $response->json();

        if ($this->dataKey !== null) {
            $body = is_array($body[$this->dataKey] ?? null) ? $body[$this->dataKey] : [];
        }

        return array_values($body);
    }
}
