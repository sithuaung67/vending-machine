<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;
use App\Services\JwtService;

/** Issues JWT access tokens for the REST API (task 17). */
final class AuthApiController
{
    public function __construct(private ?User $users = null)
    {
        $this->users ??= new User();
    }

    public function login(Request $request): void
    {
        $v = Validator::make($request->body, ['email' => 'required|email', 'password' => 'required']);
        if ($v->fails()) {
            Response::json(['errors' => $v->errors()], 422);
        }
        $user = $this->users->findByEmail((string) $request->input('email'));
        if (!$user || !password_verify((string) $request->input('password'), $user['password'])) {
            Response::json(['error' => 'Invalid credentials'], 401);
        }
        $cfg = config()['jwt'];
        $jwt = new JwtService($cfg['secret'], $cfg['ttl']);
        $token = $jwt->issue(['sub' => (int) $user['id'], 'role' => $user['role'], 'name' => $user['name']]);

        Response::json([
            'token_type' => 'Bearer',
            'expires_in' => $cfg['ttl'],
            'access_token' => $token,
            'user' => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
        ]);
    }
}
