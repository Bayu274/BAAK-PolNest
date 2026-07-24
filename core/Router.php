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
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Halaman Tidak Ditemukan | BAAK Politeknik Nest</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
            <div class="text-center">
                <h1 class="display-1 fw-bold text-muted">404</h1>
                <p class="fs-4 text-secondary mb-4">Halaman tidak ditemukan</p>
                <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>" class="btn btn-primary">
                    <i class="bi bi-house-door me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </body>
        </html>
        <?php
        return;
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