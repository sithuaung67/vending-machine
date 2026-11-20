<?php
declare(strict_types=1);
namespace App\Core;

use ReflectionClass;

/** Minimal router: manual registration + PHP 8 attribute routing, with path params & middleware. */
final class Router
{
    /** @var array<int, array{method:string,regex:string,handler:mixed,middleware:array,params:array}> */
    private array $routes = [];

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, '/' . trim($path, '/'));
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex'  => '#^' . $regex . '$#',
            'handler'=> $handler,
            'middleware' => $middleware,
            'params' => $params,
        ];
    }

    public function get(string $p, array|callable $h, array $m = []): void    { $this->add('GET', $p, $h, $m); }
    public function post(string $p, array|callable $h, array $m = []): void   { $this->add('POST', $p, $h, $m); }
    public function put(string $p, array|callable $h, array $m = []): void    { $this->add('PUT', $p, $h, $m); }
    public function delete(string $p, array|callable $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    /** Scan a controller for #[Route] attributes and register them (attribute routing). */
    public function registerController(string $class): void
    {
        $ref = new ReflectionClass($class);
        foreach ($ref->getMethods() as $method) {
            foreach ($method->getAttributes(Route::class) as $attr) {
                /** @var Route $route */
                $route = $attr->newInstance();
                $this->add($route->method, $route->path, [$class, $method->getName()], $route->middleware);
            }
        }
    }

    public function dispatch(Request $request): void
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) continue;
            if ($route['method'] !== $request->method) { $allowed[] = $route['method']; continue; }

            foreach ($route['middleware'] as $mw) {
                (new $mw())->handle($request);   // middleware halts (redirect/JSON) on failure
            }

            array_shift($matches);
            $args = array_combine($route['params'], $matches) ?: [];

            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $handler = [new $class(), $action];
            }
            $handler($request, ...array_values($args));
            return;
        }

        if ($allowed) {
            $request->wantsJson()
                ? Response::json(['error' => 'Method Not Allowed'], 405)
                : Response::html('<h1>405 Method Not Allowed</h1>', 405);
        }
        $request->wantsJson()
            ? Response::json(['error' => 'Not Found'], 404)
            : Response::html(View::render('errors/404', [], 'layout'), 404);
    }
}
