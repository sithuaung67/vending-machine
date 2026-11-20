<?php
declare(strict_types=1);
/*
 | Loads .env (simple parser) into getenv()/$_ENV and exposes config().
 */
(function (): void {
    $envFile = dirname(__DIR__) . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k); $v = trim($v, " \t\"'");
            if (getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
        }
    }
})();

function env(string $key, mixed $default = null): mixed {
    $v = getenv($key);
    if ($v === false) return $default;
    return match (strtolower($v)) { 'true' => true, 'false' => false, 'null' => null, default => $v };
}

function config(): array {
    return [
        'app'  => ['env' => env('APP_ENV', 'production'), 'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', '')],
        'db'   => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'), 'port' => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'vending_machine'),
            'username' => env('DB_USERNAME', 'root'), 'password' => env('DB_PASSWORD', ''),
        ],
        'jwt'  => ['secret' => env('JWT_SECRET', 'insecure-dev-secret'), 'ttl' => (int) env('JWT_TTL', 3600)],
    ];
}
