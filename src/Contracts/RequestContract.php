<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Contracts;

use Psr\Http\Message\ResponseInterface;
use Rooberthh\HttpTools\Enums\Method;
use Rooberthh\Sdk\Connector;
use Rooberthh\Sdk\Http\BaseUri;

/**
 * One API operation: where to send it, and what its result is.
 *
 * An SDK Author implements it once per endpoint, as a `final readonly` class
 * that takes the operation's inputs through its constructor. A Request only
 * describes; the Connector turns it into PSR-7 and sends it, so a Consumer can
 * send one directly (`$connector->send(new GetProject('prj_1'))`) or through a
 * Resource.
 *
 * @template T the result, and so what {@see Connector::send()} returns
 */
interface RequestContract
{
    public function method(): Method;

    /**
     * Relative to the Connector's base URI, so it can't carry a query or leave
     * the base ({@see BaseUri::resolve()}). Values taken from input must be
     * encoded by the Request, e.g. `'projects/' . rawurlencode($this->id)`.
     */
    public function path(): string;

    /**
     * What the response means: a Data Object, a list of them, null for an
     * operation that returns nothing, or the response itself.
     *
     * @param ResponseInterface $response
     *
     * @return T
     */
    public function result(ResponseInterface $response): mixed;
}
