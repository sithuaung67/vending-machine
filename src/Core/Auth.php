<?php
declare(strict_types=1);
namespace App\Core;

use App\Models\User;

/** Session-based authentication + role storage. */
final class Auth
{
    public static function start(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Verify credentials and log the user in. */
    public static function attempt(string $email, string $password): bool
    {
        $user = (new User())->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            self::login($user);
            return true;
        }
        return false;
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    }

    public static function check(): bool { self::start(); return isset($_SESSION['user']); }
    public static function user(): ?array { self::start(); return $_SESSION['user'] ?? null; }
    public static function id(): ?int { return self::user()['id'] ?? null; }
    public static function role(): ?string { return self::user()['role'] ?? null; }
    public static function isAdmin(): bool { return self::role() === 'admin'; }
}
