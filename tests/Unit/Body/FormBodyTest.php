<?php

declare(strict_types=1);

use Rooberthh\Sdk\Http\Body\FormBody;

it('encodes the fields as a form submission, dropping nulls', function (): void {
    $body = new FormBody(['name' => 'Acme Inc', 'tags' => ['a', 'b'], 'skip' => null]);

    expect($body->content())->toBe('name=Acme+Inc&tags%5B0%5D=a&tags%5B1%5D=b')
        ->and($body->contentType())->toBe('application/x-www-form-urlencoded');
});
