<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Rooberthh\Sdk\Http\Query;

/**
 * A Request that sends a Query: Filters, sorting, pagination and the like.
 * The path can't carry one ({@see RequestContract::path()}), so this is the
 * only place it comes from.
 */
interface HasQuery
{
    /**
     * The parameters, in the order they are sent, e.g.
     * `['status' => $this->status?->value, 'page' => $this->page]`.
     * Null values are left out, so optional ones need no `if`. How each value
     * is encoded is described on {@see Query}.
     *
     * @return array<string, mixed>
     */
    public function query(): array;
}
