<?php
declare(strict_types=1);
/*
 | Creates the schema and seeds demo data.
 | Usage:  php database/migrate.php
 | Reads DB settings from .env (MySQL in production, SQLite for local/tests).
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

// On first boot the database container may still be starting — retry for ~30s.
for ($attempt = 1; ; $attempt++) {
    try {
        $pdo = Database::connection();
        break;
    } catch (PDOException $e) {
        if ($attempt >= 15) throw $e;
        fwrite(STDERR, "Database not ready (attempt $attempt): {$e->getMessage()}\n");
        sleep(2);
    }
}
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$auto = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
$decimal = $driver === 'mysql' ? 'DECIMAL(10,3)' : 'NUMERIC';
$ts = $driver === 'mysql' ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : "TEXT DEFAULT (datetime('now'))";

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id $auto,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at $ts
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id $auto,
    name VARCHAR(100) NOT NULL,
    price $decimal NOT NULL,
    quantity_available INT NOT NULL DEFAULT 0,
    created_at $ts
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
    id $auto,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price $decimal NOT NULL,
    total_price $decimal NOT NULL,
    created_at $ts,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)");

// Seed users (idempotent)
$have = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($have === 0) {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)');
    $stmt->execute(['Admin', 'admin@vending.test', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $stmt->execute(['User',  'user@vending.test',  password_hash('user123',  PASSWORD_DEFAULT), 'user']);
    echo "Seeded users (admin@vending.test / admin123, user@vending.test / user123)\n";
}

// Seed products (idempotent)
$have = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($have === 0) {
    $stmt = $pdo->prepare('INSERT INTO products (name, price, quantity_available) VALUES (?,?,?)');
    foreach ([['Coke', 3.99, 10], ['Pepsi', 6.885, 8], ['Water', 0.5, 20]] as $p) $stmt->execute($p);
    echo "Seeded products (Coke, Pepsi, Water)\n";
}
echo "Migration complete ($driver).\n";
