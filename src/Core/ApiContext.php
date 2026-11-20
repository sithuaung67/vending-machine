<?php
declare(strict_types=1);
namespace App\Core;

/** Holds the authenticated API user's JWT claims for the current request. */
final class ApiContext
{
    public static ?array $claims = null;
    public static function user(): ?array { return self::$claims; }
    public static function id(): ?int { return isset(self::$claims['sub']) ? (int) self::$claims['sub'] : null; }
    public static function isAdmin(): bool { return (self::$claims['role'] ?? null) === 'admin'; }
}
