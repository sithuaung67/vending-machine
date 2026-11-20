<?php
/*
 | Router script for PHP's built-in web server (used on Railway).
 | Serves existing static files directly; everything else goes to the front controller.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // let the built-in server serve the asset as-is
}
require __DIR__ . '/index.php';
