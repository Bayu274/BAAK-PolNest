<?php

class AuthController extends Controller
{
    public function showLoginForm(): void
    {
        $this->render('frontend/login');
    }

    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Lapis 1: batasi per-IP (cegah 1 sumber mencoba banyak username)
        $ipAllowed = checkRateLimit($ip, 'login', 8, 300); // 8x / 5 menit

        // Lapis 2: batasi per-username (cegah brute-force ke 1 akun spesifik)
        $usernameKey = 'login:' . strtolower($username);
        $userAllowed = checkRateLimit($ip, $usernameKey, 5, 900); // 5x / 15 menit

        if (!$ipAllowed || !$userAllowed) {
            $this->render('frontend/login', [
                'error' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa menit.'
            ]);
            return;
        }

        $adminModel = new Admin();
        $admin = $adminModel->findByUsername($username);

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: /BAAK-PolNest/dashboard');
            exit;
        }

        $this->render('frontend/login', ['error' => 'Username atau password salah']);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: /BAAK-PolNest/login');
        exit;
    }
}