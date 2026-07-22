<?php

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $dbname = 'polinest_baak';
        $username = 'root';
        $password = '';   // sesuaikan kalau MySQL kamu pakai password

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            logError("Database connection failed: " . $e->getMessage());
            if (getenv('APP_ENV') === 'production') {
                die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
            } else {
                die('Koneksi database gagal: ' . $e->getMessage());
            }
        }
    }

    return $pdo;
}