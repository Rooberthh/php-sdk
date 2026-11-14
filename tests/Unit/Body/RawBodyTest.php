<?php

declare(strict_types=1);

use Rooberthh\Sdk\Http\Body\RawBody;

it('sends the content as it is, with its Content-Type', function () {
    $body = new RawBody('<project/>', 'application/xml');

    expect($body->content())->toBe('<project/>')
        ->and($body->contentType())->toBe('application/xml');
});
