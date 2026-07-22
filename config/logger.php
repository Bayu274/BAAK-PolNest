<?php
/**
 * BAAK-PolNest - Simple File Logger
 */

if (!function_exists('logMessage')) {
    /**
     * Log message ke file harian
     * @param string $message Pesan yang akan di-log
     * @param string $level Level log (ERROR, WARNING, INFO, DEBUG)
     */
    function logMessage(string $message, string $level = 'ERROR'): void {
        $logDir = __DIR__ . '/../storage/logs';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '-';
        
        $entry = "[{$timestamp}] [{$level}] [{$ip}] [{$uri}] {$message}" . PHP_EOL;
        
        error_log($entry, 3, $logFile);
    }
}

if (!function_exists('logError')) {
    function logError(string $message): void {
        logMessage($message, 'ERROR');
    }
}

if (!function_exists('logWarning')) {
    function logWarning(string $message): void {
        logMessage($message, 'WARNING');
    }
}

if (!function_exists('logInfo')) {
    function logInfo(string $message): void {
        logMessage($message, 'INFO');
    }
}
