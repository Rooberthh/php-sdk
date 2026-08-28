<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * A body that can only be read once, like a real socket-backed response.
 *
 * Nyholm's streams are seekable and rewind on __toString(), which hides
 * double-read bugs in tests that a live HTTP client would expose.
 */
final class NonSeekableStream implements StreamInterface
{
    /** @var resource */
    private $handle;

    public function __construct(string $data)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $data);
        rewind($handle);

        $this->handle = $handle;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('Stream is not seekable.');
    }

    public function rewind(): void
    {
        throw new RuntimeException('Stream is not seekable.');
    }

    public function getContents(): string
    {
        return stream_get_contents($this->handle);
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return ftell($this->handle);
    }

    public function eof(): bool
    {
        return feof($this->handle);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Stream is not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return fread($this->handle, $length);
    }

    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
