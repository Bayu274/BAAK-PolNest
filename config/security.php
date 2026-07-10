<?php
/**
 * BAAK-PolNest - Security Layer Stub
 * Branch: feature/advisor-search
 */

if (!function_exists('checkRateLimit')) {
    /**
     * Stub untuk membatasi request per IP.
     * Implementasi database penuh akan dikerjakan di branch feature/rate-limiting.
     * Saat ini selalu mengembalikan true agar tidak memblokir pengembangan pencarian.
     */
    function checkRateLimit(string $ip, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool {
        // TODO: Akan dihubungkan ke tabel rate_limit_attempts oleh Dev 3 di branch feature/rate-limiting
        return true; 
    }
}

if (!function_exists('e')) {
    /**
     * Helper global untuk mencegah XSS pada output HTML murni (Defense-in-depth)
     */
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
