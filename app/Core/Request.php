<?php

namespace Sentinel\Core;

/**
 * HTTP Request abstraction.
 */
class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $headers;

    public function __construct()
    {
        $this->query = $_GET;
        $this->server = $_SERVER;
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $uri = strtok($uri, '?'); // Remove query string
        return rtrim($uri, '/') ?: '/';
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtolower(str_replace('-', '_', $key));
        return $this->headers[$normalized] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['HTTP_X_REAL_IP']
            ?? $this->server['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content_type', '');
        return str_contains($contentType, 'application/json');
    }

    private function parseBody(): array
    {
        if ($this->method() === 'GET') {
            return [];
        }

        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            return json_decode($raw, true) ?? [];
        }

        return $_POST;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(substr($key, 5));
                $headers[$name] = $value;
            }
        }
        // Also content type / length
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content_type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content_length'] = $this->server['CONTENT_LENGTH'];
        }
        return $headers;
    }
}
