<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpFileUploadException;
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    private Stream $fileStream;
    private int $size;
    private int $error;
    private string $fileName;
    private string $mediaType;

    public function __construct(
        Stream $fileStream,
        int $size,
        int $error,
        string $fileName,
        string $mediaType
    ) {
        if ($error !== UPLOAD_ERR_OK && $error !== UPLOAD_ERR_NO_FILE) {
            throw new HttpFileUploadException(
                "The file was not uploaded successfully.",
                500
            );
        }

        $this->fileStream = $fileStream;
        $this->size = $size;
        $this->error = $error;
        $this->fileName = $fileName;
        $this->mediaType = $mediaType;
    }

    public function getStream(): Stream
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new HttpFileUploadException(
                "The file was not uploaded successfully.",
                500
            );
        }

        return $this->fileStream;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->fileName;
    }

    public function getClientMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function moveTo(string $targetPath): void
    {
        if (empty($targetPath)) {
            throw new \InvalidArgumentException("Target path must not be empty");
        }

        if (!is_writable(dirname($targetPath))) {
            throw new \RuntimeException("The target directory is not writable: {$targetPath}");
        }

        $targetPath = realpath($targetPath) ?: $targetPath;

        $fileUri = $this->fileStream->getMetadata('uri');
        if (!$fileUri) {
            throw new \RuntimeException("Failed to retrieve file URI.");
        }

        if (is_uploaded_file($fileUri)) {
            if (!move_uploaded_file($fileUri, $targetPath)) {
                throw new \RuntimeException("Failed to move uploaded file to target path: {$targetPath}");
            }
        } else {
            $source = $this->fileStream;
            $destination = fopen($targetPath, 'wb');
            if ($destination === false) {
                throw new \RuntimeException("Failed to open target file for writing: {$targetPath}");
            }
            stream_copy_to_stream($source, $destination);
            fclose($destination);
        }
    }
}
