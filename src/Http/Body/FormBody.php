<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http\Body;

use Rooberthh\Sdk\Contracts\BodyContract;

/**
 * A Payload sent as an HTML form submission
 * (`application/x-www-form-urlencoded`), as OAuth2 token endpoints and APIs
 * such as Stripe's expect. Null fields are dropped, and nested arrays use
 * PHP's bracket notation (`tags[0]=a`).
 */
final readonly class FormBody implements BodyContract
{
    private string $content;

    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(array $fields)
    {
        $this->content = http_build_query($fields, encoding_type: PHP_QUERY_RFC1738);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function contentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}
