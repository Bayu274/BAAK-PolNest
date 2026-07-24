<?php
/**
 * BAAK-PolNest - Security Layer
 * Branch: feature/rate-limiting
 */

// Pastikan session aktif (jika belum dipanggil di index.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('generateCspNonce')) {
    /**
     * Generate CSP nonce per session untuk inline scripts.
     * Setiap view harus menambahkan nonce ini ke <script nonce="...">
     */
    function generateCspNonce(): string {
        if (empty($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csp_nonce'];
    }
}

if (!function_exists('emit_security_headers')) {
    /**
     * Memancarkan header keamanan (CSP, XSS, HSTS)
     * Harus dipanggil di index.php sebelum output HTML atau JSON apapun dikirim.
     */
    function emit_security_headers(): void {
        if (headers_sent()) return;

        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        // Content Security Policy (nonce-based untuk script-src)
        $nonce = generateCspNonce();
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "img-src 'self' data: blob:; " .
               "font-src 'self' https://cdn.jsdelivr.net data:; " .
               "connect-src 'self'; frame-ancestors 'self'; " .
               "base-uri 'self'; form-action 'self'");

        // HSTS — hanya kirim saat HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}

if (!function_exists('generateCsrfToken')) {
    /**
     * Membuat token CSRF timing-safe
     */
    function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('regenerateCsrfToken')) {
    /**
     * Regenerate CSRF token setelah form submission berhasil.
     * Token lama langsung tidak valid.
     */
    function regenerateCsrfToken(): void {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('verifyCsrfToken')) {
    /**
     * Memvalidasi token CSRF
     */
    function verifyCsrfToken(string $token): bool {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('checkRateLimit')) {
    /**
     * Granular Rate Limiting dengan Atomic Upsert dan Fail-Closed.
     * Menggunakan INSERT ... ON DUPLICATE KEY UPDATE untuk atomic increment.
     * Fail-closed: jika DB error, request ditolak (bukan dibypass).
     */
    function checkRateLimit(string $ip, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool {
        if (!function_exists('getDbConnection')) {
            require_once __DIR__ . '/database.php';
        }

        $db = null;
        try {
            $db = getDbConnection();
        } catch (Exception $e) {
            error_log("Security config gagal memanggil koneksi: " . $e->getMessage());
            return false; // Fail-closed: reject jika infrastruktur security down
        }

        $windowStart = time() - $windowSeconds;

        try {
            // Bersihkan record lama (khusus IP+endpoint ini)
            $stmtCleanup = $db->prepare(
                "DELETE FROM rate_limit_attempts 
                 WHERE ip_address = :ip AND endpoint = :endpoint AND window_start < :ws"
            );
            $stmtCleanup->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':ws' => date('Y-m-d H:i:s', $windowStart),
            ]);

            // Hitung attempt dalam window
            $stmtCount = $db->prepare(
                "SELECT COALESCE(SUM(attempt_count), 0) as total 
                 FROM rate_limit_attempts 
                 WHERE ip_address = :ip AND endpoint = :endpoint 
                 AND window_start >= :ws"
            );
            $stmtCount->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':ws' => date('Y-m-d H:i:s', $windowStart),
            ]);
            $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $currentAttempts = (int)($row['total'] ?? 0);

            if ($currentAttempts >= $maxAttempts) {
                return false; // Ditolak
            }

            // Atomic upsert — INSERT atau increment
            $stmtInsert = $db->prepare(
                "INSERT INTO rate_limit_attempts (ip_address, endpoint, window_start, attempt_count)
                 VALUES (:ip, :endpoint, NOW(), 1)
                 ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1"
            );
            $stmtInsert->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
            ]);

            return true;

        } catch (PDOException $e) {
            error_log("DB Rate Limit Error: " . $e->getMessage());
            return false; // Fail-closed on DB error
        }
    }
}

if (!function_exists('e')) {
    /**
     * Helper global untuk Defense-in-Depth XSS
     */
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('sanitizeHtmlContent')) {
    /**
     * Membersihkan HTML dari rich text editor (CKEditor/TinyMCE) sebelum disimpan ke database.
     * Mencegah XSS dari konten yang disimpan sebagai HTML mentah.
     */
    function sanitizeHtmlContent(string $dirty): string {
        static $purifier = null;

        if ($purifier === null) {
            $purifierPath = __DIR__ . '/../libs/htmlpurifier-4.15.0-standalone/HTMLPurifier.standalone.php';

            if (!file_exists($purifierPath)) {
                logError("HTMLPurifier library not found at: {$purifierPath}");
                return htmlspecialchars($dirty, ENT_QUOTES, 'UTF-8');
            }

            require_once $purifierPath;

            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,ul,ol,li,a[href],h1,h2,h3,h4,blockquote,table,thead,tbody,tr,td,th');
            $config->set('AutoFormat.RemoveEmpty', true);
            $config->set('Cache.SerializerPath', sys_get_temp_dir());

            $purifier = new HTMLPurifier($config);
        }

        return $purifier->purify($dirty);
    }
}