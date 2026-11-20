<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\ApiContext;
use App\Core\Request;
use App\Core\Response;
use App\Services\JwtService;

/** Validates the Bearer JWT for protected API routes (task 17). */
final class JwtMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->bearerToken();
        if (!$token) {
            Response::json(['error' => 'Missing bearer token'], 401);
        }
        try {
            $cfg = config()['jwt'];
            ApiContext::$claims = (new JwtService($cfg['secret'], $cfg['ttl']))->verify($token);
        } catch (\Throwable $e) {
            Response::json(['error' => 'Invalid or expired token'], 401);
        }
    }
}
