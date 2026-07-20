<?php
/**
 * BAAK-PolNest - Security Layer
 * Branch: feature/rate-limiting
 */

// Pastikan session aktif (jika belum dipanggil di index.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        
        // HSTS (Strict-Transport-Security) - Direkomendasikan jika sudah HTTPS
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
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

if (!function_exists('cleanupOldAttempts')) {
    /**
     * Menghapus log rate limit yang sudah usang dari database (Sampah > 1 jam)
     */
    function cleanupOldAttempts(PDO $db): void {
        // Menggunakan SQL Native DateTime untuk menyesuaikan tipe kolom timestamp
        $sql = "DELETE FROM rate_limit_attempts WHERE window_start < (NOW() - INTERVAL 1 HOUR)";
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            error_log("Rate Limit Cleanup Error: " . $e->getMessage());
        }
    }
}

if (!function_exists('checkRateLimit')) {
    /**
     * Granular Rate Limiting dengan Stochastic Cleanup (Sesuai Skema Dev 1)
     */
    function checkRateLimit(string $ip, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool {
        // Panggil file database jika fungsi koneksi belum tersedia
        if (!function_exists('getDbConnection')) {
            require_once __DIR__ . '/database.php';
        }
        
        try {
            // Panggil koneksi sesuai arsitektur Dev 1
            $db = getDbConnection(); 
        } catch (Exception $e) {
            error_log("Security config gagal memanggil koneksi: " . $e->getMessage());
            return true; // Fail-open sementara
        }

        // 1. Stochastic Cleanup (10% Probabilitas eksekusi)
        if (random_int(1, 10) === 1) {
            cleanupOldAttempts($db);
        }

        // Hitung batas waktu menggunakan format date PHP untuk dicocokkan dengan kolom timestamp MySQL
        $windowStartStr = date('Y-m-d H:i:s', time() - $windowSeconds);
        $currentTimeStr = date('Y-m-d H:i:s');

        try {
            // 2. Bersihkan request kadaluarsa KHUSUS untuk IP & endpoint ini
            $stmtClean = $db->prepare("DELETE FROM rate_limit_attempts WHERE ip_address = :ip AND endpoint = :endpoint AND window_start < :window_start");
            $stmtClean->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':window_start' => $windowStartStr
            ]);

            // 3. Hitung jumlah request
            $stmtCount = $db->prepare("SELECT SUM(attempt_count) as total_attempts FROM rate_limit_attempts WHERE ip_address = :ip AND endpoint = :endpoint");
            $stmtCount->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint
            ]);
            
            $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $totalAttempts = (int)($row['total_attempts'] ?? 0);

            // 4. Periksa limit
            if ($totalAttempts >= $maxAttempts) {
                return false; // Ditolak
            }

            // 5. Catat attempt baru sesuai nama kolom skema
            $stmtInsert = $db->prepare("INSERT INTO rate_limit_attempts (ip_address, endpoint, attempt_count, window_start) VALUES (:ip, :endpoint, 1, :current_time)");
            $stmtInsert->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':current_time' => $currentTimeStr
            ]);

            return true; 

        } catch (PDOException $e) {
            error_log("DB Rate Limit Error: " . $e->getMessage());
            return true; 
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
            require_once __DIR__ . '/../libs/htmlpurifier-4.15.0-standalone/HTMLPurifier.standalone.php';

            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,ul,ol,li,a[href],h1,h2,h3,h4,blockquote,table,thead,tbody,tr,td,th');
            $config->set('AutoFormat.RemoveEmpty', true);
            $config->set('Cache.SerializerPath', sys_get_temp_dir());

            $purifier = new HTMLPurifier($config);
        }

        return $purifier->purify($dirty);
    }
}