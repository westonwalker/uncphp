<?php

// Registered routes
$routes = [];

// Extracted URL parameters for the current request (e.g. {slug})
$route_params = [];

// --- Route registration helpers ---

function get(string $uri, string $view, array $middleware = []): void {
    global $routes;
    $routes[] = ['method' => 'GET', 'uri' => $uri, 'view' => $view, 'middleware' => $middleware];
}

function post(string $uri, string $view, array $middleware = []): void {
    global $routes;
    $routes[] = ['method' => 'POST', 'uri' => $uri, 'view' => $view, 'middleware' => $middleware];
}

function put(string $uri, string $view, array $middleware = []): void {
    global $routes;
    $routes[] = ['method' => 'PUT', 'uri' => $uri, 'view' => $view, 'middleware' => $middleware];
}

function delete(string $uri, string $view, array $middleware = []): void {
    global $routes;
    $routes[] = ['method' => 'DELETE', 'uri' => $uri, 'view' => $view, 'middleware' => $middleware];
}

// --- Route resolution ---

function resolve_route(string $method, string $uri): ?array {
    global $routes, $route_params;

    foreach ($routes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }

        // Convert {param} placeholders to a regex capture group
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $route['uri']);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // drop the full-match entry
            preg_match_all('/\{(\w+)\}/', $route['uri'], $names);
            $route_params = !empty($names[1]) ? array_combine($names[1], $matches) : [];
            return $route;
        }
    }

    return null;
}

// --- Middleware ---

// Middleware names are just function names defined in middleware.php.
// 'auth' calls auth(), 'check_user_role' calls check_user_role(), etc.
function run_middleware(array $names): void {
    foreach ($names as $name) {
        if (!function_exists($name)) {
            throw new RuntimeException("Unknown middleware: '{$name}'");
        }
        $name();
    }
}

// --- URL parameter helper ---

function param(string $key, mixed $default = null): mixed {
    global $route_params;
    return $route_params[$key] ?? $default;
}

// --- Dispatcher ---

function dispatch(string $method, string $uri): void {
    global $global_middleware;

    $route = resolve_route($method, $uri);

    if (!$route) {
        http_response_code(404);
        require 'views/404.php';
        return;
    }

    // Global middleware runs on every route
    run_middleware($global_middleware ?? []);

    // CSRF runs automatically on all state-changing requests
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        csrf();
    }

    // Route-specific middleware
    run_middleware($route['middleware']);

    require $route['view'];
}
