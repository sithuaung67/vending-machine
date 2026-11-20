<?php
declare(strict_types=1);
namespace App\Core;

/** Immutable snapshot of the incoming HTTP request. */
final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $path   = '/' . trim($path, '/');

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $body = $_POST;
        $raw  = file_get_contents('php://input') ?: '';
        if ($raw !== '' && str_contains(strtolower($headers['Content-Type'] ?? $_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $body = $decoded;
        }
        // Method override for HTML forms (PUT/PATCH/DELETE)
        if ($method === 'POST' && isset($body['_method'])) {
            $method = strtoupper((string) $body['_method']);
        }
        return new self($method, $path, $_GET, $body, $headers);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) if (array_key_exists($k, $this->body)) $out[$k] = $this->body[$k];
        return $out;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) return $v;
        }
        return null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization') ?? '';
        return preg_match('/Bearer\s+(.+)/i', $auth, $m) ? trim($m[1]) : null;
    }

    public function wantsJson(): bool
    {
        return str_starts_with($this->path, '/api')
            || str_contains(strtolower($this->header('Accept') ?? ''), 'application/json');
    }
}
