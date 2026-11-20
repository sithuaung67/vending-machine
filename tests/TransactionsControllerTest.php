<?php
declare(strict_types=1);
namespace Tests;

use App\Controllers\TransactionsController;
use App\Core\HttpResponseException;
use App\Core\Request;
use App\Models\Transaction;
use PHPUnit\Framework\TestCase;

/** Purchase history page: admins see everyone, users only themselves (mocked model). */
final class TransactionsControllerTest extends TestCase
{
    private function page(array $rows): array
    {
        return ['data' => $rows, 'total' => count($rows), 'page' => 1, 'per_page' => 10, 'pages' => 1];
    }

    private function render(Transaction $model): HttpResponseException
    {
        try {
            (new TransactionsController($model))->index(new Request('GET', '/transactions', [], [], []));
        } catch (HttpResponseException $e) {
            return $e;
        }
        $this->fail('Expected a response.');
    }

    public function test_admin_sees_all_transactions_with_customer(): void
    {
        $_SESSION = ['user' => ['id' => 1, 'name' => 'Admin', 'email' => 'a@x', 'role' => 'admin']];
        $model = $this->createMock(Transaction::class);
        $model->expects($this->once())->method('paginate')->with(null, 1, 10)->willReturn($this->page([[
            'id' => 7, 'user_name' => 'Alice', 'user_email' => 'alice@x.test', 'product_name' => 'Pepsi',
            'quantity' => 2, 'unit_price' => '6.885', 'total_price' => '13.770', 'created_at' => '2026-11-20 10:00:00',
        ]]));
        $model->method('summary')->willReturn(['purchases' => 1, 'units' => 2, 'revenue' => 13.77]);

        $e = $this->render($model);

        $this->assertSame(200, $e->status);
        $this->assertStringContainsString('Alice', $e->body);
        $this->assertStringContainsString('Pepsi', $e->body);
        $this->assertStringContainsString('$13.77', $e->body);
    }

    public function test_user_sees_only_own_purchases(): void
    {
        $_SESSION = ['user' => ['id' => 5, 'name' => 'Bob', 'email' => 'b@x', 'role' => 'user']];
        $model = $this->createMock(Transaction::class);
        $model->expects($this->once())->method('paginate')->with(5, 1, 10)->willReturn($this->page([]));
        $model->expects($this->once())->method('summary')->with(5)->willReturn(['purchases' => 0, 'units' => 0, 'revenue' => 0.0]);

        $e = $this->render($model);

        $this->assertStringContainsString('My purchases', $e->body);
        $this->assertStringContainsString('No purchases yet', $e->body);
        $this->assertStringNotContainsString('Customer', $e->body);
    }
}
