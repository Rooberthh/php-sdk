<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Rooberthh\Sdk\Client;
use Rooberthh\Sdk\Http\Builder;

/**
 * Reads a property assigned after parent::__construct() from defaultHeaders().
 * Only works because the default headers are read on the first request, not
 * inside the parent constructor.
 */
class LateHeaderClient extends Client
{
    private string $apiVersion;

    public function __construct(string $url, string $apiVersion, ?Builder $builder = null)
    {
        parent::__construct($url, $builder);

        $this->apiVersion = $apiVersion;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept'      => 'application/json',
            'X-Api-Version' => $this->apiVersion,
        ];
    }
}
