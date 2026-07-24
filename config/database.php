<?php

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host     = getenv('DB_HOST') ?: '127.0.0.1';
        $dbname   = getenv('DB_NAME') ?: 'polinest_baak';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?? '';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            logError("Database connection failed: " . $e->getMessage());
            die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
        }
    }

    return $pdo;
}