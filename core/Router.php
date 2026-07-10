<?php

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($requestMethod)) {
                continue;
            }

            $params = $this->matchPath($route['path'], $path);
            if ($params !== false) {
                call_user_func_array($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        echo "404 - Halaman tidak ditemukan";
    }

    private function matchPath(string $routePath, string $requestPath): array|false
    {
        // Ubah {param} jadi regex capture group
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestPath, $matches)) {
            array_shift($matches); // buang full match, sisakan captured params
            return $matches;
        }

        return false;
    }
}   