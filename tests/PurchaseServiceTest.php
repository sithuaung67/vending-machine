<?php
declare(strict_types=1);
namespace Tests;

use App\Services\PurchaseService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PurchaseServiceTest extends TestCase
{
    public function test_successful_purchase_decrements_stock_and_logs_transaction(): void
    {
        $pdo = Support::pdo();
        $id = Support::seedProduct($pdo, 'Coke', 3.99, 5);

        $result = (new PurchaseService($pdo))->execute(userId: 1, productId: $id, quantity: 2);

        $this->assertSame(2, $result['quantity']);
        $this->assertSame('7.980', $result['total_price']);
        $this->assertSame(3, $result['remaining']);

        $stock = (int) $pdo->query("SELECT quantity_available FROM products WHERE id = $id")->fetchColumn();
        $this->assertSame(3, $stock);
        $count = (int) $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function test_rejects_purchase_when_stock_is_insufficient(): void
    {
        $pdo = Support::pdo();
        $id = Support::seedProduct($pdo, 'Water', 0.5, 1);

        $this->expectException(RuntimeException::class);
        (new PurchaseService($pdo))->execute(1, $id, 999);
    }

    public function test_rejects_non_positive_quantity(): void
    {
        $pdo = Support::pdo();
        $id = Support::seedProduct($pdo, 'Pepsi', 6.885, 3);

        $this->expectException(RuntimeException::class);
        (new PurchaseService($pdo))->execute(1, $id, 0);
    }
}
