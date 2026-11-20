<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Transaction;

/**
 * Purchase history. Admins see every transaction (who bought what);
 * regular users see only their own purchases.
 */
final class TransactionsController
{
    public function __construct(private ?Transaction $transactions = null)
    {
        $this->transactions ??= new Transaction();
    }

    public function index(Request $request): void
    {
        $isAdmin = Auth::isAdmin();
        $scope = $isAdmin ? null : (int) Auth::id();

        Response::html(view('transactions/index', [
            'page' => $this->transactions->paginate($scope, (int) ($request->query['page'] ?? 1), 10),
            'summary' => $this->transactions->summary($scope),
            'isAdmin' => $isAdmin,
        ]));
    }
}
