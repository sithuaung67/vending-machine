<?php
declare(strict_types=1);
namespace App\Core;

final class Response
{
    /** When true (tests), responses throw instead of exit(). */
    public static bool $testing = false;

    public static function json(mixed $data, int $status = 200): never
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (self::$testing) throw new HttpResponseException($status, $body, 'json');
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo $body;
        exit;
    }

    public static function redirect(string $to, int $status = 302): never
    {
        if (self::$testing) throw new HttpResponseException($status, '', 'redirect', $to);
        http_response_code($status);
        header('Location: ' . $to);
        exit;
    }

    public static function html(string $html, int $status = 200): never
    {
        if (self::$testing) throw new HttpResponseException($status, $html, 'html');
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
