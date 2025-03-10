<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpResponseException;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    protected int $statusCode;
    protected string $reasonPhrase;

    public function __construct(
        string $content,
        int $statusCode = 200,
        string $contentType = 'text/html'
    ) {
        parent::__construct('w+b');
    
        $this->headers['Content-Type'] = [$contentType];
    
        if ($statusCode !== 200 && $statusCode !== 201 && $statusCode !== 204) {
            $this->reasonPhrase = !empty($content) ? $content : $this->getReasonPhrase();
            $this->body->write($this->reasonPhrase);
            $this->statusCode = $statusCode;
        } else {
            $this->body->write($content);
            $this->statusCode = $statusCode;
        }

        $this->headers['Content-Length'] = [strlen($content)];
    }
    

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase !== '') {
            return $this->reasonPhrase;
        }

        $defaultPhrases = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            419 => 'Request Expired',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
        ];

        return $defaultPhrases[$this->statusCode] ?? '';
    }

    public function withStatus(int $statusCode, string $reasonPhrase = ''): static
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new HttpResponseException('Invalid HTTP status code.');
        }

        $clone = clone $this;
        $clone->statusCode = $statusCode;
        $clone->reasonPhrase = $reasonPhrase ?: $clone->getReasonPhrase();

        return $clone;
    }

    public function render(): void
    {
        $this->body->rewind();

        while (!$this->body->eof()) {
            echo $this->body->read(8192);
        }
    }
}
