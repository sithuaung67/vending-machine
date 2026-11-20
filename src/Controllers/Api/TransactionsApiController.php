<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiContext;
use App\Core\Request;
use App\Core\Response;
use App\Models\Transaction;

/** GET /api/transactions — admins get all transactions, users get their own. */
final class TransactionsApiController
{
    public function __construct(private ?Transaction $transactions = null)
    {
        $this->transactions ??= new Transaction();
    }

    public function index(Request $request): void
    {
        $scope = ApiContext::isAdmin() ? null : (int) ApiContext::id();
        $perPage = max(1, min(100, (int) ($request->query['per_page'] ?? 10)));

        Response::json([
            'data' => $this->transactions->paginate($scope, (int) ($request->query['page'] ?? 1), $perPage),
            'summary' => $this->transactions->summary($scope),
        ]);
    }
}
