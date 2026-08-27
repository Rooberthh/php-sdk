<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Pagination;

use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Contracts\ClientContract;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\FilterBuilder;
use Rooberthh\Sdk\Response;

/**
 * Pages numbered from a starting page — `?page=2&per_page=50`.
 *
 * Keeps asking for the next page until one comes back short, which is how
 * these APIs signal the end.
 *
 * @template T of DataObjectContract
 *
 * @extends Paginator<T>
 */
final class PagedPaginator extends Paginator
{
    /**
     * @param class-string<T> $dataObject
     * @param int             $firstPage  some APIs number pages from 0
     * @param ClientContract $client
     * @param RequestInterface $request
     * @param int $perPage
     * @param string $pageKey
     * @param string $perPageKey
     * @param ?string $dataKey
     * @param int $maxPages
     */
    public function __construct(
        ClientContract $client,
        RequestInterface $request,
        string $dataObject,
        private readonly int $perPage = 100,
        private readonly string $pageKey = 'page',
        string $perPageKey = 'per_page',
        private readonly int $firstPage = 1,
        ?string $dataKey = null,
        int $maxPages = 1000,
    ) {
        parent::__construct(
            $client,
            FilterBuilder::for($request)
                ->where($pageKey, $firstPage)
                ->where($perPageKey, $perPage)
                ->apply(),
            $dataObject,
            $dataKey,
            $maxPages,
        );
    }

    protected function next(RequestInterface $request, Response $response): ?RequestInterface
    {
        if (count($this->entries($response)) < $this->perPage) {
            return null;
        }

        parse_str($request->getUri()->getQuery(), $query);

        return FilterBuilder::for($request)
            ->where($this->pageKey, ((int) ($query[$this->pageKey] ?? $this->firstPage)) + 1)
            ->apply();
    }
}
