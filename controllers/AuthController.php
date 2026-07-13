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