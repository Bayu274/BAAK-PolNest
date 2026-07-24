<?php

class Controller
{
    protected function render(string $view, array $data = [], bool|string $useLayout = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        generateCsrfToken();

        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            logError("View not found: {$view}");
            if (getenv('APP_ENV') === 'production') {
                die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
            } else {
                die("View tidak ditemukan: {$view}");
            }
        }

        if ($useLayout === true) {
            // Backend admin layout
            ob_start();
            require $viewPath;
            $content = ob_get_clean();
            $layoutPath = __DIR__ . '/../views/backend/layout.php';
            require $layoutPath;
            return;
        }

        if ($useLayout === 'frontend') {
            // Frontend public layout
            ob_start();
            require $viewPath;
            $content = ob_get_clean();
            $layoutPath = __DIR__ . '/../views/frontend/layout.php';
            require $layoutPath;
            return;
        }

        // No layout (fragment)
        require $viewPath;
    }

    protected function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['admin_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}