<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme;

use Http\Message\Authentication\Bearer;
use Psr\Http\Client\ClientInterface;
use Rooberthh\Sdk\Connector;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Resources\ProjectResource;

/**
 * The Client of a fictional Acme API, built the way an SDK Author would build
 * one on this framework. Tests drive it to check the framework end to end.
 */
final class AcmeConnector extends Connector
{
    public const string BASE_URL = 'https://api.acme.test/v1';

    /**
     * @param string           $token the Consumer's API token, sent as a Bearer token
     * @param ?ClientInterface $http
     */
    public function __construct(string $token, ?ClientInterface $http = null)
    {
        parent::__construct(self::BASE_URL, new Bearer($token), http: $http);
    }

    public function projects(): ProjectResource
    {
        return new ProjectResource($this);
    }

    protected function userAgentString(): string
    {
        return 'acme-php/1.0.0 ' . parent::userAgentString();
    }
}
