<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpServerRequestException;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    private array $serverParams;
    private array $queryParams;
    private array $parsedBody;
    private array $cookieParams;
    private array $uploadedFiles;
    protected array $attributes;

    public function __construct(
        array $attributes = []
    ) {
        parent::__construct();

        $this->serverParams = $_SERVER;
        $this->queryParams = $_GET;
        $this->parsedBody = $_POST;
        $this->cookieParams = $_COOKIE;
        $this->uploadedFiles = $_FILES;
        $this->attributes = $attributes;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getParsedBody(): mixed
    {
        if ($this->parsedBody) {
            return $this->parsedBody;
        }
    
        $contentType = $this->getHeaderLine('Content-Type');
    
        if (str_contains($contentType, 'application/json')) {
            $jsonData = json_decode($this->body->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpServerRequestException("Invalid JSON payload", 400);
            }
            return $jsonData;

        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($this->body->getContents(), $parsed);
            return $parsed;
        }
    
        return null;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone; 
    }

    public function withParsedBody($data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
