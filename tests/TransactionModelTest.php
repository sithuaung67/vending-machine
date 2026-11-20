<?php
declare(strict_types=1);
namespace Tests;

use App\Models\Product;
use App\Models\Transaction;
use App\Services\PurchaseService;
use PHPUnit\Framework\TestCase;

/** Purchase-history queries against an in-memory SQLite database. */
final class TransactionModelTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Support::pdo();
        $this->pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Alice','a@x.test','x','user'), ('Bob','b@x.test','x','user')");
        $coke = Support::seedProduct($this->pdo, 'Coke', 3.99, 10);
        $water = Support::seedProduct($this->pdo, 'Water', 0.5, 10);

        $buy = new PurchaseService($this->pdo);
        $buy->execute(1, $coke, 2);   // Alice: 2 × Coke  = 7.98
        $buy->execute(2, $water, 3);  // Bob:   3 × Water = 1.50
        $buy->execute(1, $water, 1);  // Alice: 1 × Water = 0.50
    }

    public function test_paginate_returns_buyer_and_product_newest_first(): void
    {
        $page = (new Transaction($this->pdo))->paginate(null, 1, 10);

        $this->assertSame(3, $page['total']);
        $this->assertSame('Alice', $page['data'][0]['user_name']);
        $this->assertSame('Water', $page['data'][0]['product_name']);
        $this->assertSame('Coke', $page['data'][2]['product_name']);
    }

    public function test_paginate_can_be_scoped_to_one_user(): void
    {
        $page = (new Transaction($this->pdo))->paginate(2, 1, 10);

        $this->assertSame(1, $page['total']);
        $this->assertSame('Bob', $page['data'][0]['user_name']);
    }

    public function test_paginate_splits_into_pages(): void
    {
        $page = (new Transaction($this->pdo))->paginate(null, 2, 2);

        $this->assertSame(2, $page['pages']);
        $this->assertCount(1, $page['data']);
    }

    public function test_summary_totals_purchases_units_and_revenue(): void
    {
        $all = (new Transaction($this->pdo))->summary();
        $this->assertSame(3, $all['purchases']);
        $this->assertSame(6, $all['units']);
        $this->assertEqualsWithDelta(9.98, $all['revenue'], 0.0001);

        $alice = (new Transaction($this->pdo))->summary(1);
        $this->assertSame(2, $alice['purchases']);
        $this->assertEqualsWithDelta(8.48, $alice['revenue'], 0.0001);
    }

    public function test_product_with_history_is_detected(): void
    {
        $products = new Product($this->pdo);
        $this->assertTrue($products->hasTransactions(1));

        $fresh = Support::seedProduct($this->pdo, 'Juice', 2.0, 5);
        $this->assertFalse($products->hasTransactions($fresh));
    }
}
