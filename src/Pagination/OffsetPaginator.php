<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Pagination;

use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\FilterBuilder;
use Rooberthh\Sdk\Response;

/**
 * A moving window over the result set — `?offset=100&limit=50`.
 *
 * Advances the offset by the limit until a page comes back short, which is how
 * these APIs signal the end.
 *
 * @template T of DataObjectContract
 *
 * @extends Paginator<T>
 */
final class OffsetPaginator extends Paginator
{
    /**
     * @param class-string<T> $dataObject
     * @param ClientContract $client
     * @param RequestInterface $request
     * @param int $limit
     * @param string $offsetKey
     * @param string $limitKey
     * @param ?string $dataKey
     * @param int $maxPages
     */
    public function __construct(
        ClientContract $client,
        RequestInterface $request,
        string $dataObject,
        private readonly int $limit = 100,
        private readonly string $offsetKey = 'offset',
        string $limitKey = 'limit',
        ?string $dataKey = null,
        int $maxPages = 1000,
    ) {
        parent::__construct(
            $client,
            FilterBuilder::for($request)
                ->where($offsetKey, 0)
                ->where($limitKey, $limit)
                ->apply(),
            $dataObject,
            $dataKey,
            $maxPages,
        );
    }

    protected function next(RequestInterface $request, Response $response): ?RequestInterface
    {
        if (count($this->entries($response)) < $this->limit) {
            return null;
        }

        parse_str($request->getUri()->getQuery(), $query);

        return FilterBuilder::for($request)
            ->where($this->offsetKey, ((int) ($query[$this->offsetKey] ?? 0)) + $this->limit)
            ->apply();
    }
}
