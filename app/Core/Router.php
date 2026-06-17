<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = ['csrf']): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        if ($method === 'POST' && ($_POST['_method'] ?? '') !== '') {
            $method = strtoupper((string) $_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            $this->runMiddleware($route['middleware'], $params);
            [$controller, $action] = $route['handler'];
            (new $controller())->$action(...array_values($params));
            return;
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Page not found'], 'layouts/guest');
    }

    private function runMiddleware(array $middleware, array $params): void
    {
        foreach ($middleware as $entry) {
            if ($entry === 'csrf') {
                (new CsrfMiddleware())->handle();
            } elseif ($entry === 'auth') {
                (new AuthMiddleware())->handle();
            } elseif (str_starts_with($entry, 'role:')) {
                (new RoleMiddleware(substr($entry, 5)))->handle();
            }
        }
    }
}
