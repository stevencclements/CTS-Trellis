<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpMessageException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected string $version;
    protected array $headers;
    protected Stream $body;

    public function __construct(
        string $mode = 'rb',
        string $resource = 'php://temp'
    ) {
        $versionNumber = explode('/', $_SERVER['SERVER_PROTOCOL']);

        $this->body = new Stream($mode, $resource);
        $this->headers = getallheaders();
        $this->version = $versionNumber[1];
    }

    public function hasHeader(string $name): bool
    {
        $normalizedName = strtolower($name);

        foreach ($this->headers as $name => $values) {
            if (strtolower($name) === $normalizedName) {
                return true;
            }
        }

        return false;
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): array
    {
        $normalizedName = strtolower($name);

        foreach ($this->headers as $name => $values) {
            if (strtolower($name) === $normalizedName) {
                return $values;
            }
        }

        return [];
    }

    public function getHeaderLine(string $name): string
    {
        $values = $this->getHeader($name);

        if (empty($values)) {
            return '';
        }

        return implode(', ', $values);
    }

    public function getBody(): Stream
    {
        return $this->body;
    }

    public function withProtocolVersion(string $version): static
    {
        if (!preg_match('/^\d+\.\d+$/', $version)) {
            throw new HttpMessageException(
                "The HTTP Message could not be processed. The HTTP protocol version $version is invalid.",
                500
            );
        }

        $clone = clone $this;
        $clone->version = $version;

        return $clone;
    }

    public function withHeader(string $name, $values): static
    {
        if (!preg_match('/^[a-zA-Z0-9\'-]+$/', $name)) {
            throw new HttpMessageException(
                "The HTTP Message could not be processed. The HTTP header $name is invalid.",
                500
            );
        }

        $headerValues = is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            if (!is_scalar($value) || preg_match('/[\r\n]/', (string)$value)) {
                throw new HttpMessageException(
                    "The HTTP Message could not be processed. The HTTP header $name is invalid.",
                    500
                );
            }
        }

        $clone = clone $this;

        $normalizedName = strtolower($name);
        $clone->headers[$normalizedName] = $headerValues;

        return $clone;
    }

    public function withAddedHeader(string $name, $values): static
    {
        if (!preg_match('/^[a-zA-Z0-9\'-]+$/', $name)) {
            throw new HttpMessageException(
                "The HTTP Message could not be processed. The HTTP header name $name is invalid.",
                500
            );
        }

        $headerValues = is_array($values) ? $values : [$values];

        foreach ($headerValues as $value) {
            if (!is_scalar($value) || preg_match('/[\r\n]/', (string) $value)) {
                throw new HttpMessageException(
                    "The HTTP Message could not be processed. The HTTP header name $name is invalid.",
                    500
                );
            }
        }

        $clone = clone $this;

        $normalizedHeaderName = strtolower($name);

        if (isset($clone->headers[$normalizedHeaderName])) {
            $clone->headers[$normalizedHeaderName] = array_merge(
                $clone->headers[$normalizedHeaderName],
                $headerValues
            );
        } else {
            $clone->headers[$normalizedHeaderName] = $headerValues;
        }

        return $clone;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;

        if (!$body instanceof Stream) {
            throw new HttpMessageException(
                "The HTTP Message could not be processed. The HTTP body must be an instance of `Cts\\Trellis\\Core\\Stream`.",
                500
            );
        }

        $clone->body = $body;

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $normalizedHeaderName = strtolower($name);

        if (isset($clone->headers[$normalizedHeaderName])) {
            unset($clone->headers[$normalizedHeaderName]);
        }

        return $clone;
    }
}
