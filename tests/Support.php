<?php
declare(strict_types=1);
namespace Tests;

use PDO;

/** Test helpers — builds an isolated in-memory SQLite database with the app schema. */
final class Support
{
    public static function pdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT, role TEXT DEFAULT "user", created_at TEXT)');
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price NUMERIC, quantity_available INTEGER DEFAULT 0, created_at TEXT)');
        $pdo->exec('CREATE TABLE transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, product_id INTEGER, quantity INTEGER, unit_price NUMERIC, total_price NUMERIC, created_at TEXT)');
        return $pdo;
    }

    public static function seedProduct(PDO $pdo, string $name, float $price, int $qty): int
    {
        $s = $pdo->prepare('INSERT INTO products (name, price, quantity_available) VALUES (?,?,?)');
        $s->execute([$name, $price, $qty]);
        return (int) $pdo->lastInsertId();
    }
}
