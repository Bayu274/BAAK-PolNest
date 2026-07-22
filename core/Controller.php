<?php

class Controller
{
    protected function render(string $view, array $data = [], bool $useLayout = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        generateCsrfToken();

        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            die("View tidak ditemukan: {$view}");
        }

        if ($useLayout) {
            // Render view ke buffer dulu, hasilnya jadi $content untuk layout
            ob_start();
            require $viewPath;
            $content = ob_get_clean();

            $layoutPath = __DIR__ . '/../views/backend/layout.php';
            require $layoutPath;
            return;
        }

        require $viewPath;
    }

    protected function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['admin_id'])) {
            header('Location: /BAAK-PolNest/login');
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