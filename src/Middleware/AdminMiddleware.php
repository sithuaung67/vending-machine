<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/** Role-based access control — Admin only (task 4). */
final class AdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        if (!Auth::isAdmin()) {
            $request->wantsJson()
                ? Response::json(['error' => 'Forbidden — admin only'], 403)
                : Response::html(\App\Core\View::render('errors/403', [], 'layout'), 403);
        }
    }
}
