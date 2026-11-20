<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Route;
use App\Core\Validator;
use App\Models\Product;
use App\Services\PurchaseService;

/**
 * Handles CRUD for products (admin) + the purchase flow (any authenticated user).
 * Purchase routes use PHP 8 attribute routing (tasks 6, 11).
 */
final class ProductsController
{
    public function __construct(
        private ?Product $products = null,
        private ?PurchaseService $purchases = null,
    ) {
        $this->products ??= new Product();
        $this->purchases ??= new PurchaseService();
    }

    private function rules(): array
    {
        return [
            'name' => 'required|min_len:1',
            'price' => 'required|numeric|gt:0',
            'quantity_available' => 'required|integer|gte:0',
        ];
    }

    /** List all products with pagination + sorting (tasks 7, 8). */
    public function index(Request $request): void
    {
        $result = $this->products->paginate(
            page: (int) ($request->query['page'] ?? 1),
            perPage: 5,
            sort: (string) ($request->query['sort'] ?? 'id'),
            dir: (string) ($request->query['dir'] ?? 'desc'),
        );
        Response::html(view('products/index', ['page' => $result, 'isAdmin' => Auth::isAdmin()]));
    }

    public function create(Request $request): void
    {
        Response::html(view('products/create'));
    }

    public function store(Request $request): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect('/products');
        $v = Validator::make($request->body, $this->rules());
        if ($v->fails()) {
            set_old($request->only(['name', 'price', 'quantity_available']));
            flash('error', implode(' ', array_values($v->firstErrors())));
            Response::redirect('/products/create');
        }
        $this->products->create($request->only(['name', 'price', 'quantity_available']));
        clear_old();
        flash('success', 'Product created.');
        Response::redirect('/products');
    }

    public function edit(Request $request, string $id): void
    {
        $product = $this->products->find((int) $id);
        if (!$product) Response::html(view('errors/404'), 404);
        Response::html(view('products/edit', ['product' => $product]));
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect('/products');
        $v = Validator::make($request->body, $this->rules());
        if ($v->fails()) {
            set_old($request->only(['name', 'price', 'quantity_available']));
            flash('error', implode(' ', array_values($v->firstErrors())));
            Response::redirect("/products/{$id}/edit");
        }
        $this->products->update((int) $id, $request->only(['name', 'price', 'quantity_available']));
        clear_old();
        flash('success', 'Product updated.');
        Response::redirect('/products');
    }

    public function destroy(Request $request, string $id): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect('/products');
        if ($this->products->hasTransactions((int) $id)) {
            flash('error', 'This product has purchase history and cannot be deleted. Set its quantity to 0 instead.');
            Response::redirect('/products');
        }
        $this->products->delete((int) $id);
        flash('success', 'Product deleted.');
        Response::redirect('/products');
    }

    /** Show the purchase form for a product (attribute routing). */
    #[Route('GET', '/products/{id}/purchase', middleware: [\App\Middleware\AuthMiddleware::class], name: 'products.purchase.form')]
    public function purchaseForm(Request $request, string $id): void
    {
        $product = $this->products->find((int) $id);
        if (!$product) Response::html(view('errors/404'), 404);
        Response::html(view('products/purchase', ['product' => $product]));
    }

    /** Process a purchase (attribute routing, tasks 6 & 11). */
    #[Route('POST', '/products/{id}/purchase', middleware: [\App\Middleware\AuthMiddleware::class], name: 'products.purchase')]
    public function purchase(Request $request, string $id): void
    {
        if (!verify_csrf($request->input('_csrf'))) Response::redirect("/products/{$id}/purchase");

        $v = Validator::make($request->body, ['quantity' => 'required|integer|gt:0']);
        if ($v->fails()) {
            flash('error', 'Quantity must be a positive whole number.');
            Response::redirect("/products/{$id}/purchase");
        }

        try {
            $result = $this->purchases->execute(
                userId: (int) Auth::id(),
                productId: (int) $id,
                quantity: (int) $request->input('quantity'),
            );
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            Response::redirect("/products/{$id}/purchase");
        }

        flash('success', sprintf(
            'Purchased %d × item for %s USD. %d left in stock.',
            $result['quantity'], $result['total_price'], $result['remaining']
        ));
        Response::redirect('/products');
    }
}
