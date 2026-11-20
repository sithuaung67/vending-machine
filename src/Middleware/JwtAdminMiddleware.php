<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\ApiContext;
use App\Core\Request;
use App\Core\Response;

/** Admin-only guard for API routes (used after JwtMiddleware). */
final class JwtAdminMiddleware
{
    public function handle(Request $request): void
    {
        if (!ApiContext::isAdmin()) {
            Response::json(['error' => 'Forbidden — admin only'], 403);
        }
    }
}
