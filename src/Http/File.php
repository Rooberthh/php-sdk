<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\StreamInterface;
use Rooberthh\Sdk\Exceptions\InvalidFile;
use RuntimeException;

/**
 * A file to upload as one part of a {@see Body\MultipartBody}.
 *
 * The filename is what the API is told the file is called, and is always
 * given, so an upload never leaks a local path. Without a Content-Type, one is
 * guessed from the filename's extension.
 */
final readonly class File
{
    /**
     * @param StreamInterface $contents
     * @param string          $filename
     * @param ?string         $contentType
     *
     * @throws InvalidFile when the Content-Type would break out of the part's headers
     */
    private function __construct(
        public StreamInterface $contents,
        public string $filename,
        public ?string $contentType,
    ) {
        // Upload handlers often pass on the MIME type the browser claimed, so
        // it can't be trusted not to carry a header of its own.
        if ($contentType !== null && preg_match('/[\r\n]/', $contentType) === 1) {
            throw InvalidFile::contentTypeHasLineBreak();
        }
    }

    /**
     * A file on disk, named after it unless `$filename` is given.
     *
     * @param string  $path
     * @param ?string $filename
     * @param ?string $contentType
     *
     * @throws RuntimeException when the file can't be opened
     * @throws InvalidFile       when the Content-Type contains a line break
     */
    public static function fromPath(string $path, ?string $filename = null, ?string $contentType = null): self
    {
        return new self(
            Psr17FactoryDiscovery::findStreamFactory()->createStreamFromFile($path),
            $filename ?? basename($path),
            $contentType,
        );
    }

    /**
     * Contents already in memory, such as a generated report.
     *
     * @param string  $contents
     * @param string  $filename
     * @param ?string $contentType
     *
     * @throws InvalidFile when the Content-Type contains a line break
     */
    public static function fromString(string $contents, string $filename, ?string $contentType = null): self
    {
        return new self(Psr17FactoryDiscovery::findStreamFactory()->createStream($contents), $filename, $contentType);
    }

    /**
     * @param StreamInterface $stream
     * @param string          $filename
     * @param ?string         $contentType
     *
     * @throws InvalidFile when the Content-Type contains a line break
     */
    public static function fromStream(StreamInterface $stream, string $filename, ?string $contentType = null): self
    {
        return new self($stream, $filename, $contentType);
    }
}
