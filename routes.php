<?php
declare(strict_types=1);
/*
 | Application routes. Returns a closure that registers everything on the Router.
 | CRUD routes are registered manually (task 10); the purchase action is registered
 | via PHP 8 attribute routing on ProductsController (task 11).
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\ProductsController;
use App\Controllers\TransactionsController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ProductsApiController;
use App\Controllers\Api\TransactionsApiController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\JwtMiddleware;
use App\Middleware\JwtAdminMiddleware;

return function (Router $r): void {

    // --- Home ---
    $r->get('/', fn ($request) => \App\Core\Response::redirect('/products'));

    // --- Authentication (web) ---
    $r->get('/login',      [AuthController::class, 'showLogin']);
    $r->post('/login',     [AuthController::class, 'login']);
    $r->get('/register',   [AuthController::class, 'showRegister']);
    $r->post('/register',  [AuthController::class, 'register']);
    $r->post('/logout',    [AuthController::class, 'logout']);

    // --- Products CRUD (web) — management is Admin-only (RBAC) ---
    $r->get('/products',                 [ProductsController::class, 'index'],   [AuthMiddleware::class]);
    $r->get('/products/create',          [ProductsController::class, 'create'],  [AdminMiddleware::class]);
    $r->post('/products',                [ProductsController::class, 'store'],   [AdminMiddleware::class]);
    $r->get('/products/{id}/edit',       [ProductsController::class, 'edit'],    [AdminMiddleware::class]);
    $r->put('/products/{id}',            [ProductsController::class, 'update'],  [AdminMiddleware::class]);
    $r->delete('/products/{id}',         [ProductsController::class, 'destroy'], [AdminMiddleware::class]);

    // --- Purchase (web) — registered via #[Route] attributes on the controller ---
    $r->registerController(ProductsController::class);

    // --- Purchase history (admin: everyone, user: own purchases) ---
    $r->get('/transactions',             [TransactionsController::class, 'index'], [AuthMiddleware::class]);

    // --- REST API (JWT) ---
    $r->post('/api/login',               [AuthApiController::class, 'login']);
    $r->get('/api/products',             [ProductsApiController::class, 'index'],   [JwtMiddleware::class]);
    $r->get('/api/products/{id}',        [ProductsApiController::class, 'show'],    [JwtMiddleware::class]);
    $r->post('/api/products',            [ProductsApiController::class, 'store'],   [JwtMiddleware::class, JwtAdminMiddleware::class]);
    $r->put('/api/products/{id}',        [ProductsApiController::class, 'update'],  [JwtMiddleware::class, JwtAdminMiddleware::class]);
    $r->delete('/api/products/{id}',     [ProductsApiController::class, 'destroy'], [JwtMiddleware::class, JwtAdminMiddleware::class]);
    $r->post('/api/products/{id}/purchase', [ProductsApiController::class, 'purchase'], [JwtMiddleware::class]);
    $r->get('/api/transactions',         [TransactionsApiController::class, 'index'], [JwtMiddleware::class]);
};
