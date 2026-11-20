<?php
declare(strict_types=1);
namespace App\Core;

use PDO;
use PDOException;

/**
 * PDO connection class. Single shared connection (lazy singleton).
 * Supports MySQL (production) and SQLite (tests / local).
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct() {}

    public static function connection(?array $config = null): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $config ??= config()['db'];

        if (($config['connection'] ?? 'mysql') === 'sqlite') {
            $dsn = 'sqlite:' . ($config['database'] ?? ':memory:');
            $user = $pass = null;
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'], $config['port'], $config['database']
            );
            $user = $config['username'];
            $pass = $config['password'];
        }

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            if (($config['connection'] ?? 'mysql') === 'sqlite') {
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }
        return self::$pdo;
    }

    /** Inject a connection (used by tests to pass an in-memory SQLite PDO). */
    public static function set(PDO $pdo): void { self::$pdo = $pdo; }
    public static function reset(): void { self::$pdo = null; }
}
