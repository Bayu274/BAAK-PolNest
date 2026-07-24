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
            mkdir($logDir, 0750, true);
        }
        
        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '-', PHP_URL_PATH);
        
        // Strip newlines untuk cegah log injection
        $message = str_replace(["\r", "\n"], '', $message);
        $uri = str_replace(["\r", "\n"], '', $uri);
        
        $entry = "[{$timestamp}] [{$level}] [{$ip}] [{$uri}] {$message}" . PHP_EOL;
        
        error_log($entry, 3, $logFile);

        // Cleanup log lama (>30 hari) — sekali per jam
        if (empty($_SESSION['_log_cleanup']) || (time() - $_SESSION['_log_cleanup']) > 3600) {
            $_SESSION['_log_cleanup'] = time();
            $files = glob($logDir . '/*.log');
            $cutoff = time() - (30 * 24 * 3600);
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                }
            }
        }
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
