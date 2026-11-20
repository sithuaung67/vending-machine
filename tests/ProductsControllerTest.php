<?php
declare(strict_types=1);
namespace Tests;

use App\Controllers\ProductsController;
use App\Core\HttpResponseException;
use App\Core\Request;
use App\Models\Product;
use App\Services\PurchaseService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductsController using dependency injection + mocking (tasks 14, 15).
 * The Product model and PurchaseService are mocked, so the controller is tested
 * independently of the database.
 */
final class ProductsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    private function request(string $method, string $path, array $body = [], array $query = []): Request
    {
        return new Request($method, $path, $query, $body, []);
    }

    public function test_index_renders_the_product_list(): void
    {
        $products = $this->createMock(Product::class);
        $products->expects($this->once())->method('paginate')->willReturn([
            'data' => [['id' => 1, 'name' => 'Coke', 'price' => 3.99, 'quantity_available' => 10]],
            'total' => 1, 'page' => 1, 'per_page' => 5, 'pages' => 1, 'sort' => 'id', 'dir' => 'desc',
        ]);

        $controller = new ProductsController($products, $this->createMock(PurchaseService::class));

        try {
            $controller->index($this->request('GET', '/products', [], ['page' => '1']));
            $this->fail('Expected a response to be emitted.');
        } catch (HttpResponseException $e) {
            $this->assertSame(200, $e->status);
            $this->assertStringContainsString('Coke', $e->body);
        }
    }

    public function test_store_creates_a_product_and_redirects(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->once())->method('create')
                 ->with($this->callback(fn ($d) => $d['name'] === 'Sprite'))
                 ->willReturn(5);

        $controller = new ProductsController($products, $this->createMock(PurchaseService::class));
        $body = ['name' => 'Sprite', 'price' => '2.5', 'quantity_available' => '4', '_csrf' => 'token'];

        try {
            $controller->store($this->request('POST', '/products', $body));
            $this->fail('Expected a redirect.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->status);
            $this->assertSame('/products', $e->location);
        }
    }

    public function test_store_rejects_invalid_input_without_creating(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->never())->method('create');

        $controller = new ProductsController($products, $this->createMock(PurchaseService::class));
        $body = ['name' => '', 'price' => '0', 'quantity_available' => '-1', '_csrf' => 'token'];

        try {
            $controller->store($this->request('POST', '/products', $body));
            $this->fail('Expected a redirect.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->status);
            $this->assertSame('/products/create', $e->location);
        }
    }

    public function test_purchase_delegates_to_the_purchase_service(): void
    {
        $_SESSION['_csrf'] = 'token';
        $purchases = $this->createMock(PurchaseService::class);
        $purchases->expects($this->once())->method('execute')->willReturn([
            'transaction_id' => 1, 'product_id' => 1, 'quantity' => 2,
            'unit_price' => '3.99', 'total_price' => '7.980', 'remaining' => 8,
        ]);

        $controller = new ProductsController($this->createMock(Product::class), $purchases);
        $body = ['quantity' => '2', '_csrf' => 'token'];

        try {
            $controller->purchase($this->request('POST', '/products/1/purchase', $body), '1');
            $this->fail('Expected a redirect.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->status);
            $this->assertSame('/products', $e->location);
        }
    }

    /** Runs a controller action and returns the emitted response. */
    private function capture(callable $action): HttpResponseException
    {
        try {
            $action();
        } catch (HttpResponseException $e) {
            return $e;
        }
        $this->fail('Expected a response to be emitted.');
    }

    private function controller(?Product $products = null, ?PurchaseService $purchases = null): ProductsController
    {
        return new ProductsController(
            $products ?? $this->createMock(Product::class),
            $purchases ?? $this->createMock(PurchaseService::class),
        );
    }

    public function test_store_rejects_invalid_csrf_token(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->never())->method('create');

        $body = ['name' => 'Sprite', 'price' => '2.5', 'quantity_available' => '4', '_csrf' => 'wrong'];
        $e = $this->capture(fn () => $this->controller($products)->store($this->request('POST', '/products', $body)));

        $this->assertSame(302, $e->status);
    }

    public function test_store_rejects_non_numeric_price(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->never())->method('create');

        $body = ['name' => 'Sprite', 'price' => 'abc', 'quantity_available' => '4', '_csrf' => 'token'];
        $e = $this->capture(fn () => $this->controller($products)->store($this->request('POST', '/products', $body)));

        $this->assertSame('/products/create', $e->location);
    }

    public function test_store_accepts_zero_quantity(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->once())->method('create')->willReturn(9);

        $body = ['name' => 'Juice', 'price' => '1.25', 'quantity_available' => '0', '_csrf' => 'token'];
        $e = $this->capture(fn () => $this->controller($products)->store($this->request('POST', '/products', $body)));

        $this->assertSame('/products', $e->location);
    }

    public function test_update_saves_valid_changes(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->once())->method('update')
                 ->with(1, $this->callback(fn ($d) => $d['price'] === '4.50'));

        $body = ['name' => 'Coke', 'price' => '4.50', 'quantity_available' => '10', '_csrf' => 'token'];
        $e = $this->capture(fn () => $this->controller($products)->update($this->request('PUT', '/products/1', $body), '1'));

        $this->assertSame('/products', $e->location);
    }

    public function test_update_rejects_negative_price(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->never())->method('update');

        $body = ['name' => 'Coke', 'price' => '-1', 'quantity_available' => '10', '_csrf' => 'token'];
        $e = $this->capture(fn () => $this->controller($products)->update($this->request('PUT', '/products/1', $body), '1'));

        $this->assertSame('/products/1/edit', $e->location);
    }

    public function test_destroy_deletes_the_product(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->expects($this->once())->method('delete')->with(3);

        $e = $this->capture(fn () => $this->controller($products)
            ->destroy($this->request('DELETE', '/products/3', ['_csrf' => 'token']), '3'));

        $this->assertSame('/products', $e->location);
    }

    public function test_destroy_refuses_product_with_purchase_history(): void
    {
        $_SESSION['_csrf'] = 'token';
        $products = $this->createMock(Product::class);
        $products->method('hasTransactions')->with(1)->willReturn(true);
        $products->expects($this->never())->method('delete');

        $e = $this->capture(fn () => $this->controller($products)
            ->destroy($this->request('DELETE', '/products/1', ['_csrf' => 'token']), '1'));

        $this->assertSame('/products', $e->location);
        $this->assertStringContainsString('purchase history', $_SESSION['_flash']['error'] ?? '');
    }

    public function test_edit_returns_404_for_missing_product(): void
    {
        $products = $this->createMock(Product::class);
        $products->method('find')->willReturn(null);

        $e = $this->capture(fn () => $this->controller($products)->edit($this->request('GET', '/products/99/edit'), '99'));

        $this->assertSame(404, $e->status);
    }

    public function test_purchase_form_returns_404_for_missing_product(): void
    {
        $products = $this->createMock(Product::class);
        $products->method('find')->willReturn(null);

        $e = $this->capture(fn () => $this->controller($products)
            ->purchaseForm($this->request('GET', '/products/99/purchase'), '99'));

        $this->assertSame(404, $e->status);
    }

    /** @dataProvider invalidQuantities */
    public function test_purchase_rejects_invalid_quantity(string $qty): void
    {
        $_SESSION['_csrf'] = 'token';
        $purchases = $this->createMock(PurchaseService::class);
        $purchases->expects($this->never())->method('execute');

        $e = $this->capture(fn () => $this->controller(null, $purchases)
            ->purchase($this->request('POST', '/products/1/purchase', ['quantity' => $qty, '_csrf' => 'token']), '1'));

        $this->assertSame('/products/1/purchase', $e->location);
    }

    public static function invalidQuantities(): array
    {
        return ['zero' => ['0'], 'negative' => ['-3'], 'decimal' => ['1.5'], 'empty' => [''], 'text' => ['two']];
    }

    public function test_purchase_shows_error_when_out_of_stock(): void
    {
        $_SESSION['_csrf'] = 'token';
        $purchases = $this->createMock(PurchaseService::class);
        $purchases->method('execute')->willThrowException(new \RuntimeException('Insufficient stock.'));

        $e = $this->capture(fn () => $this->controller(null, $purchases)
            ->purchase($this->request('POST', '/products/1/purchase', ['quantity' => '50', '_csrf' => 'token']), '1'));

        $this->assertSame('/products/1/purchase', $e->location);
        $this->assertSame('Insufficient stock.', $_SESSION['_flash']['error'] ?? null);
    }

    public function test_purchase_rejects_invalid_csrf_token(): void
    {
        $_SESSION['_csrf'] = 'token';
        $purchases = $this->createMock(PurchaseService::class);
        $purchases->expects($this->never())->method('execute');

        $e = $this->capture(fn () => $this->controller(null, $purchases)
            ->purchase($this->request('POST', '/products/1/purchase', ['quantity' => '1', '_csrf' => 'bad']), '1'));

        $this->assertSame('/products/1/purchase', $e->location);
    }
}
