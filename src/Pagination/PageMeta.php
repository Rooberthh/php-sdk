<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Pagination;

use Rooberthh\Sdk\Concerns\DataObjects\CanCreateInstances;
use Rooberthh\Sdk\Contracts\DataObjectContract;

/**
 * Where a page sits in the result set.
 *
 * Every field is optional because APIs disagree about which they send — take
 * what your API gives you and ignore the rest. Keys arrive snake_case and are
 * matched to these properties automatically, and fields not listed here are
 * ignored rather than rejected.
 */
final readonly class PageMeta implements DataObjectContract
{
    use CanCreateInstances;

    public function __construct(
        public ?int $currentPage = null,
        public ?int $perPage = null,
        public ?int $lastPage = null,
        public ?int $total = null,
        public ?int $from = null,
        public ?int $to = null,
    ) {}

    /**
     * Whether another page exists, as far as the API said. Null when it sent
     * nothing to judge by.
     */
    public function hasMorePages(): ?bool
    {
        if ($this->currentPage !== null && $this->lastPage !== null) {
            return $this->currentPage < $this->lastPage;
        }

        if ($this->to !== null && $this->total !== null) {
            return $this->to < $this->total;
        }

        return null;
    }
}
