<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/** Requires an authenticated session. */
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            $request->wantsJson()
                ? Response::json(['error' => 'Unauthenticated'], 401)
                : Response::redirect('/login');
        }
    }
}
