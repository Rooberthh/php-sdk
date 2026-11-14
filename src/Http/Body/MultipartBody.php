<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Http\Body;

use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Http\Message\StreamInterface;
use Rooberthh\Sdk\Contracts\BodyContract;
use Rooberthh\Sdk\Http\File;

/**
 * A `multipart/form-data` submission, for uploading files.
 *
 * Each field is a text value or a {@see File}, or a list of them to send
 * several parts under one name (`attachments => [$a, $b]`). Null fields are
 * dropped, as in a form. The content is a stream, so large uploads aren't
 * copied into a string, and it can be read once: a Request builds a new
 * MultipartBody each time it is sent.
 */
final readonly class MultipartBody implements BodyContract
{
    private StreamInterface $content;

    private string $boundary;

    /**
     * @param array<string, null|File|float|int|list<File|float|int|string>|string> $fields
     */
    public function __construct(array $fields)
    {
        $builder = new MultipartStreamBuilder();

        foreach ($fields as $name => $value) {
            foreach (is_array($value) ? $value : [$value] as $part) {
                if ($part !== null) {
                    $this->add($builder, $name, $part);
                }
            }
        }

        $this->content = $builder->build();
        $this->boundary = $builder->getBoundary();
    }

    public function content(): StreamInterface
    {
        return $this->content;
    }

    public function contentType(): string
    {
        return "multipart/form-data; boundary={$this->boundary}";
    }

    /**
     * @param MultipartStreamBuilder $builder
     * @param string                 $name
     * @param File|float|int|string  $part
     */
    private function add(MultipartStreamBuilder $builder, string $name, File|float|int|string $part): void
    {
        // The builder writes names and filenames into the part's headers
        // unescaped, so the Content-Disposition is built here.
        $disposition = sprintf('form-data; name="%s"', $this->escape($name));

        if (!$part instanceof File) {
            $builder->addResource($name, (string) $part, ['headers' => ['Content-Disposition' => $disposition]]);

            return;
        }

        $headers = ['Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $this->escape($part->filename))];

        if ($part->contentType !== null) {
            $headers['Content-Type'] = $part->contentType;
        }

        // The filename is passed too, so a missing Content-Type is guessed from it.
        $builder->addResource($name, $part->contents, ['filename' => $part->filename, 'headers' => $headers]);
    }

    /**
     * A field name or filename made safe to quote in a part's headers, the way
     * browsers do it (WHATWG HTML): `"`, CR and LF are percent-encoded.
     *
     * @param string $value
     */
    private function escape(string $value): string
    {
        return str_replace(['"', "\r", "\n"], ['%22', '%0D', '%0A'], $value);
    }
}
