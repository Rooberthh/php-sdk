<?php

declare(strict_types=1);

use Nyholm\Psr7\Stream;
use Rooberthh\Sdk\Exceptions\InvalidFile;
use Rooberthh\Sdk\Http\Body\MultipartBody;
use Rooberthh\Sdk\Http\File;

/**
 * @param MultipartBody $body
 *
 * @return array{string, string} the boundary and the content
 */
function multipartParts(MultipartBody $body): array
{
    expect($body->contentType())->toStartWith('multipart/form-data; boundary=');

    return [substr($body->contentType(), strlen('multipart/form-data; boundary=')), (string) $body->content()];
}

it('sends text fields and files as parts', function () {
    [$boundary, $content] = multipartParts(new MultipartBody([
        'name'   => 'Acme',
        'report' => File::fromString('a,b', 'report.csv', 'text/csv'),
    ]));

    expect($content)->toBe(
        "--{$boundary}\r\n"
        . "Content-Disposition: form-data; name=\"name\"\r\n"
        . "\r\n"
        . "Acme\r\n"
        . "--{$boundary}\r\n"
        . "Content-Disposition: form-data; name=\"report\"; filename=\"report.csv\"\r\n"
        . "Content-Type: text/csv\r\n"
        . "\r\n"
        . "a,b\r\n"
        . "--{$boundary}--\r\n",
    );
});

it('guesses a missing Content-Type from the filename', function () {
    [, $content] = multipartParts(new MultipartBody(['logo' => File::fromString('x', 'logo.png')]));

    expect($content)->toContain("Content-Type: image/png\r\n");
});

it('sends a list as several parts under one name, and drops nulls', function () {
    [, $content] = multipartParts(new MultipartBody([
        'attachments' => [File::fromString('1', 'a.txt'), File::fromString('2', 'b.txt')],
        'note'        => null,
        'count'       => 2,
    ]));

    expect(substr_count($content, 'name="attachments"'))->toBe(2)
        ->and($content)->toContain('filename="a.txt"')
        ->and($content)->toContain('filename="b.txt"')
        ->and($content)->toContain("name=\"count\"\r\n\r\n2\r\n")
        ->and($content)->not->toContain('name="note"');
});

it('names a file on disk after it, never by its path', function () {
    [, $content] = multipartParts(new MultipartBody([
        'logo' => File::fromPath(__DIR__ . '/../../Fixtures/Acme/files/logo.png'),
    ]));

    expect($content)->toContain('filename="logo.png"')
        ->and($content)->not->toContain('Fixtures');
});

it('sends a stream as it is', function () {
    [, $content] = multipartParts(new MultipartBody([
        'data' => File::fromStream(Stream::create('streamed'), 'data.bin', 'application/octet-stream'),
    ]));

    expect($content)->toContain("\r\n\r\nstreamed\r\n");
});

it('escapes quotes and line breaks in names and filenames', function () {
    [, $content] = multipartParts(new MultipartBody([
        "fi\"eld\r\nX-Injected: 1" => File::fromString('x', "a\".png\r\nX-Injected: 1"),
    ]));

    expect($content)->toContain('name="fi%22eld%0D%0AX-Injected: 1"; filename="a%22.png%0D%0AX-Injected: 1"')
        ->and($content)->not->toContain("\r\nX-Injected");
});

it('refuses a Content-Type with a line break', function () {
    File::fromString('x', 'a.png', "image/png\r\nX-Injected: 1");
})->throws(InvalidFile::class);
