<?php

// BASE_URL terdeteksi otomatis dari environment (host, port) supaya tidak perlu
// diubah manual tiap developer punya setup XAMPP berbeda (port 8080, 80, dst).
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Validasi host — hanya izinkan karakter hostname yang valid (cegah Host Header Injection)
$host = preg_match('/^[a-zA-Z0-9._-]+$/', $rawHost) ? $rawHost : 'localhost';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = rtrim($scriptDir, '/');

define('BASE_URL', $protocol . $host . $scriptDir . '/');
define('BASE_PATH', $scriptDir);
define('APP_ENV', getenv('APP_ENV') ?: 'development');