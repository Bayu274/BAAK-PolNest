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
        $sql = "DELETE FROM rate_limit_attempts WHERE last_attempt < (UNIX_TIMESTAMP() - 3600)";
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            error_log("Rate Limit Cleanup Error: " . $e->getMessage());
        }
    }
}

if (!function_exists('checkRateLimit')) {
    /**
     * Granular Rate Limiting dengan Stochastic Cleanup
     */
    function checkRateLimit(string $ip, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool {
        // Ambil instance PDO global dari database.php (pastikan database.php sudah dimuat)
        global $db; 
        
        if (!$db) {
            error_log("Security config tidak dapat menemukan koneksi database global.");
            return true; // Fail-open sementara jika koneksi belum ada, agar aplikasi tidak crash total
        }

        // 1. Stochastic Cleanup (10% Probabilitas eksekusi)
        // Meniadakan kebutuhan Cron Job, ditarik saat request masuk secara acak
        if (random_int(1, 10) === 1) {
            cleanupOldAttempts($db);
        }

        $currentTime = time();
        $windowStart = $currentTime - $windowSeconds;

        try {
            // 2. Bersihkan request kadaluarsa KHUSUS untuk IP & endpoint ini agar kalkulasi akurat
            $stmtClean = $db->prepare("DELETE FROM rate_limit_attempts WHERE ip_address = :ip AND endpoint = :endpoint AND last_attempt < :windowStart");
            $stmtClean->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':windowStart' => $windowStart
            ]);

            // 3. Hitung jumlah request yang dilakukan IP tersebut ke endpoint ini dalam rentang waktu yang tersisa
            $stmtCount = $db->prepare("SELECT SUM(attempts) as total_attempts FROM rate_limit_attempts WHERE ip_address = :ip AND endpoint = :endpoint");
            $stmtCount->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint
            ]);
            
            $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $totalAttempts = (int)($row['total_attempts'] ?? 0);

            // 4. Periksa limit
            if ($totalAttempts >= $maxAttempts) {
                return false; // Ditolak, IP mencapai batas maksimal
            }

            // 5. Jika aman, catat attempt baru
            $stmtInsert = $db->prepare("INSERT INTO rate_limit_attempts (ip_address, endpoint, attempts, last_attempt) VALUES (:ip, :endpoint, 1, :current_time)");
            $stmtInsert->execute([
                ':ip' => $ip,
                ':endpoint' => $endpoint,
                ':current_time' => $currentTime
            ]);

            return true; // Diizinkan

        } catch (PDOException $e) {
            error_log("DB Rate Limit Error: " . $e->getMessage());
            // Jika tabel belum dibuat, fail-open agar sistem tidak berhenti berfungsi
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