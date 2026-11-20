<?php
declare(strict_types=1);

use App\Core\Auth;

/** HTML-escape. */
function e(mixed $v): string { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }

/** Flash messages (one-request session storage). */
function flash(?string $key = null, ?string $msg = null): mixed {
    Auth::start();
    if ($key !== null && $msg !== null) { $_SESSION['_flash'][$key] = $msg; return null; }
    if ($key !== null) { $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v; }
    return null;
}

/** Remember old form input after a validation failure. */
function old(string $key, mixed $default = ''): string {
    Auth::start();
    return e($_SESSION['_old'][$key] ?? $default);
}
function set_old(array $data): void { Auth::start(); $_SESSION['_old'] = $data; }
function clear_old(): void { Auth::start(); unset($_SESSION['_old']); }

/** CSRF protection for web forms. */
function csrf_token(): string {
    Auth::start();
    return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function verify_csrf(?string $token): bool {
    Auth::start();
    return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

/** Format a USD amount: at least 2 decimals, a 3rd only when needed (6.885, 3.99, 0.50). */
function money(mixed $v): string {
    $s = number_format((float) $v, 3, '.', ',');
    return str_ends_with($s, '0') ? substr($s, 0, -1) : $s;
}

/** True when the current URL path starts with the given prefix (for nav highlighting). */
function nav_active(string $prefix): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return str_starts_with($path, $prefix) ? ' class="active"' : '';
}

function view(string $tpl, array $data = [], ?string $layout = 'layout'): string {
    return App\Core\View::render($tpl, $data, $layout);
}
