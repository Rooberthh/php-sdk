<?php

declare(strict_types=1);

namespace Rooberthh\Sdk;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Rooberthh\Sdk\Contracts\DataObjectContract;
use Rooberthh\Sdk\Pagination\PageMeta;
use Traversable;

/**
 * One page of results, and where it sits in the whole.
 *
 * This is a single page as the API returned it — iterate it for the rows it
 * holds. To walk every page instead, use a {@see Paginator}.
 *
 * @template T of DataObjectContract
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class PaginatedResponse implements Countable, IteratorAggregate
{
    /**
     * @param DataObjectContract $items
     * @param PageMeta $meta
     */
    public function __construct(
        public array $items,
        public PageMeta $meta,
    ) {}

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * The rows on this page — not the total, which is {@see PageMeta::$total}.
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
