<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    private array $validMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD'
    ];
    protected $requestTarget;
    protected $method;
    protected $uri;

    public function __construct()
    {
        parent::__construct();

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = new Uri();

        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($query !== '') {
            $target .= '?' . $query;
        }

        $this->requestTarget = empty($target) ? '/' : $target;
    }

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('/\s/', $requestTarget)) {
            throw new HttpRequestException(
                "The HTTP Request could not be processed. The request target cannot contain whitespace.",
                500
            );
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function withMethod(string $method): static
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->validMethods, true)) {
            throw new HttpRequestException("Invalid HTTP method: $method", 400);
        }

        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost && $uri->getHost() !== '') {
            $host = $uri->getHost();
            
            $clone->headers['host'] = [$host];
        }

        return $clone;
    }
}
