<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Rooberthh\Sdk\Http\Builder;
use Rooberthh\Sdk\Response;

interface ClientContract
{
    /** @return array<int, Plugin> */
    public function defaultPlugins(): array;

    /** @return array<string, string> */
    public function defaultHeaders(): array;

    public function url(): string;

    public function send(RequestInterface $request): Response;

    public function builder(): Builder;

    public function getLastResponse(): ?Response;
}
