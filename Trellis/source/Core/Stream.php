<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpStreamException;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    protected mixed $stream;
    protected array $metadata;
    protected string $mode;
    protected int $size;

    public function __construct(
        string $mode = 'rb',
        string $resource = 'php://temp'
    ) {
        $this->stream = fopen($resource, $mode);

        if (!$this->stream) {
            throw new HttpStreamException(
                "The HTTP Stream could not be opened. The $resource resource is not valid.",
                500
            );
        }

        $stats = fstat($this->stream);

        $this->metadata = stream_get_meta_data($this->stream);
        $this->mode = $this->metadata['mode'];
        $this->size = $stats['size'] ?? 0;
    }

    public function __toString(): string
    {
        if (!$this->isSeekable() || !$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return stream_get_contents($this->stream);
        } catch (\RuntimeException) {
            return '';
        }
    }


    public function isSeekable(): bool
    {
        return $this->metadata['seekable'] ?? false;
    }

    public function isReadable(): bool
    {
        $readableModes = [
            'r',
            'rb',
            'r+',
            'w+',
            'a+',
            'x+',
            'c+',
            'w+b'
        ];

        return in_array(
            strtolower($this->mode),
            $readableModes,
            true
        );
    }

    public function isWritable(): bool
    {
        $writableModes = [
            'w',
            'w+',
            'wb',
            'w+b',
            'w+',
            'a+',
            'x+',
            'c+'
        ];

        return in_array(
            strtolower($this->mode),
            $writableModes,
            true
        );
    }

    public function getMetadata(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. The current stream is not readable.",
                500
            );
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. No content exists in the stream.",
                500
            );
        }

        return $contents;
    }

    public function tell(): int
    {
        $position = ftell($this->stream);

        if ($position === false) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. The read/write pointer for the stream could not be located.",
                500
            );
        }

        return $position;
    }

    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable() || fseek($this->stream, $offset, $whence) === -1) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. The current stream is not seekable.",
                500
            );
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function read(int $length): string
    {
        if (!$this->isReadable() || $length <= 0) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. The current stream is empty or is not readable.",
                500
            );
        }

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new HttpStreamException(
                "The HTTP Stream could not be read. No data found in the current Stream."
            );
        }

        return $data;
    }

    public function write(string $content): int
    {
        if (!$this->isWritable()) {
            throw new HttpStreamException("The HTTP Stream could not be written to. Stream is not writable.", 500);
        }

        $writeSize = fwrite($this->stream, $content);

        if ($writeSize === false) {
            throw new HttpStreamException("The HTTP Stream could not be written to. The write operation failed.", 500);
        }

        clearstatcache(true, stream_get_meta_data($this->stream)['uri']);
        $this->size = fstat($this->stream)['size'] ?? $this->size;

        return $writeSize;
    }


    public function detach(): mixed
    {
        if ($this->stream === null) {
            return null;
        }
        
        $resource = $this->stream;
        $this->stream = null;

        return $resource;
    }


    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
