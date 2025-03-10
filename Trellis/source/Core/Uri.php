<?php

/**
 * @package     Cts\Trellis
 * @author      Clementine Solutions
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Cts\Trellis\Core;

use Cts\Trellis\Exceptions\HttpUriException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme;
    private string $authority = '';
    private string $userInfo = '';
    private string $host;
    private int $port;
    private string $path;
    private string $query;
    private string $fragment;

    public function __construct()
    {
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $this->validateScheme($scheme);
        $this->scheme = $scheme;

        $host = $this->sanitizeHost($_SERVER['HTTP_HOST']);
        $this->host = $host;

        $port = $_SERVER['SERVER_PORT'];
        $this->validatePort($port);
        $this->port = intval($port);
        
        if (!$this->getStandardPort($this->scheme)) {
            $this->authority = $this->host . ':' . $this->port;
        }

        $path = $this->sanitizePath(strtok($_SERVER['REQUEST_URI'], '?'));
        $this->path = $path;

        $query = $this->sanitizeQuery($_SERVER['QUERY_STRING']);
        $this->query = $query;

        $fragment = $this->sanitizeFragment($_GET['fragment'] ?? '');
        $this->fragment = $fragment;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }

        if ($this->authority) {
            $uri .= '//' . $this->authority;
        }

        $path = $this->path;

        if ($this->authority && $path && $path[0] !== '/') {
            $path = '/' . $path;
        } elseif (!$this->authority && strpos($path, '//') === 0) {
            $path = '/' . ltrim($path, '/');
        }

        $uri .= $path;

        if ($this->query) {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme ? strtolower($this->scheme) : '';
    }

    public function getAuthority(): string
    {
        return $this->authority ? $this->authority : '';
    }

    public function getUserInfo(): string
    {
        return '';
    }

    public function getHost(): string
    {
        return $this->host ? strtolower($this->host) : '';
    }

    public function getPort(): ?int
    {
        return ($this->port !== $this->getStandardPort($this->scheme)) ? $this->port : null;
    }

    public function getPath(): string
    {
        $path = $this->percentEncode($this->path, 'path');

        return $path;
    }

    public function getQuery(): string
    {
        if (empty($this->query)) {
            return '';
        }

        $query = $this->percentEncode($this->query, 'query');
    
        return $query;
    }

    public function getFragment(): string
    {
        if (empty($this->fragment)) {
            return '';
        }
    
        $fragment = $this->percentEncode($this->fragment, 'fragment');
    
        return $fragment;
    }

    public function withScheme(string $scheme): static
    {
        if (!preg_match('/^[a-z][a-z0-9+\-.]*$/i', $scheme)) {
            throw new HttpUriException(
                "The specified URI could not be loaded. The $scheme scheme is invalid.",
                500
            );
        }

        $new = clone $this;
        $new->scheme = strtolower($scheme);

        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $userInfo = '';

        $new = clone $this;
        $new->userInfo = $userInfo;

        return $new;
    }

    public function withHost(string $host): static
    {
        if (empty($host)) {
            throw new HttpUriException(
                "The specified URI could not be loaded. The $host host is invalid.",
                500
            );
        }

        $new = clone $this;
        $new->host = strtolower($host);

        return $new;
    }

    public function withPort(?int $port): static
    {
        if ($port !== null && !$this->validatePort(strval($port))) {
            throw new HttpUriException(
                "The specified URL could not be loaded. The $port port is invalid.",
                500
            );
        }

        $new = clone $this;
        $new->port = $port ?? $this->getStandardPort($this->scheme);

        return $new;
    }

    public function withPath(string $path): static
    {
        if (str_contains($path, '?') || str_contains($path, '#')) {
            throw new HttpUriException(
                "The specified URL could not be loaded. The path cannot contain query or fragment delimiters.",
                500
            );
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withQuery(string $query): static
    {
        if (str_contains($query, '#')) {
            throw new HttpUriException(
                "The specified URL could not be loaded. Query strings cannot contain a fragment delimiter",
                500
            );
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(string $fragment): static
    {
        if (str_contains($fragment, '#')) {
            throw new HttpUriException(
                "The specified URL could not be loaded. Fragments cannot contain a fragment delimiter",
                500
            );
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    private function percentEncode(string $input, string $context): string
    {
        $reservedCharsByContext = [
            'path' => ['/', ':', '@', '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '='],
            'query' => ['!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=', ':', '@', '/', '?'],
            'fragment' => ['!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=', ':', '@', '/', '?', '#']
        ];

        if (!isset($reservedCharsByContext[$context])) {
            throw new HttpUriException(
                "The specified URL could not the loaded. The encoding context $context is invalid.",
                500
            );
        }

        $reservedChars = $reservedCharsByContext[$context];

        return preg_replace_callback('/[^a-zA-Z0-9_\-\.~' . preg_quote(implode('', $reservedChars), '/') . ']/', function ($matches) {
            return rawurlencode($matches[0]);
        }, $input);
    }

    private function getStandardPort(string $scheme): ?int
    {
        return match ($scheme) {
            'http' => 80,
            'https' => 443,
            'default' => null
        };
    }

    private function validateScheme(string $scheme): void
    {
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new HttpUriException(
                "The specified URI could not be loaded. The $scheme scheme is invalid.",
                500
            );
        }
    }

    private function sanitizeHost(string $host): string
    {
        return filter_var($host, FILTER_SANITIZE_URL);
    }

    private function validatePort(string $port): void
    {
        if (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535) {
            throw new HttpUriException(
                "The specified URI could not be loaded. The port $port is invalid.",
                500
            );
        }
    }

    private function sanitizePath(string $path): string
    {
        return filter_var($path, FILTER_SANITIZE_URL);
    }

    private function sanitizeQuery(string $query): string
    {
        return filter_var($query, FILTER_SANITIZE_URL);
    }

    private function sanitizeFragment(string $fragment): string
    {
        return filter_var($fragment, FILTER_SANITIZE_URL);
    }
}
