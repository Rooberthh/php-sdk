<?php

declare(strict_types=1);

use Rooberthh\Sdk\Http\Body\JsonBody;

it('encodes the Payload as JSON, as given', function (array|object $payload, string $expected): void {
    $body = new JsonBody($payload);

    expect($body->content())->toBe($expected)
        ->and($body->contentType())->toBe('application/json');
})->with([
    'an array' => [['name' => 'Acme', 'tags' => ['a']], '{"name":"Acme","tags":["a"]}'],
    'an object by its public properties' => [new class {
        public string $name = 'Acme';

        public ?string $description = null;
    }, '{"name":"Acme","description":null}'],
    'a JsonSerializable' => [new class implements JsonSerializable {
        public function jsonSerialize(): array
        {
            return ['custom' => true];
        }
    }, '{"custom":true}'],
]);

it('refuses a Payload that is not valid JSON', function (): void {
    new JsonBody(['bad' => "\xB1\x31"]);
})->throws(JsonException::class);
