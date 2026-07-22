<?php

// BASE_URL terdeteksi otomatis dari environment (host, port) supaya tidak perlu
// diubah manual tiap developer punya setup XAMPP berbeda (port 8080, 80, dst).
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = rtrim($scriptDir, '/');

define('BASE_URL', $protocol . $host . $scriptDir . '/');
define('APP_ENV', 'development');