<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Product;
use App\Services\PurchaseService;

/** RESTful API for the vending machine (task 16). JWT-protected via middleware. */
final class ProductsApiController
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

    public function index(Request $request): void
    {
        Response::json(['data' => $this->products->paginate(
            page: (int) ($request->query['page'] ?? 1),
            perPage: (int) ($request->query['per_page'] ?? 10),
            sort: (string) ($request->query['sort'] ?? 'id'),
            dir: (string) ($request->query['dir'] ?? 'desc'),
        )]);
    }

    public function show(Request $request, string $id): void
    {
        $product = $this->products->find((int) $id);
        $product ? Response::json(['data' => $product]) : Response::json(['error' => 'Not found'], 404);
    }

    public function store(Request $request): void
    {
        $v = Validator::make($request->body, $this->rules());
        if ($v->fails()) Response::json(['errors' => $v->errors()], 422);
        $id = $this->products->create($request->only(['name', 'price', 'quantity_available']));
        Response::json(['data' => $this->products->find($id)], 201);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->products->find((int) $id)) Response::json(['error' => 'Not found'], 404);
        $v = Validator::make($request->body, $this->rules());
        if ($v->fails()) Response::json(['errors' => $v->errors()], 422);
        $this->products->update((int) $id, $request->only(['name', 'price', 'quantity_available']));
        Response::json(['data' => $this->products->find((int) $id)]);
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->products->find((int) $id)) Response::json(['error' => 'Not found'], 404);
        if ($this->products->hasTransactions((int) $id)) {
            Response::json(['error' => 'Product has purchase history and cannot be deleted. Set quantity_available to 0 instead.'], 409);
        }
        $this->products->delete((int) $id);
        Response::json(['message' => 'Deleted'], 200);
    }

    public function purchase(Request $request, string $id): void
    {
        $v = Validator::make($request->body, ['quantity' => 'required|integer|gt:0']);
        if ($v->fails()) Response::json(['errors' => $v->errors()], 422);
        try {
            $result = $this->purchases->execute((int) ApiContext::id(), (int) $id, (int) $request->input('quantity'));
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 409);
        }
        Response::json(['message' => 'Purchase successful', 'data' => $result], 201);
    }
}
