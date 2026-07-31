<?php
/**
 * BAAK-PolNest - Tools: Reset/Perbaiki Akun Admin
 * -------------------------------------------------
 * Digunakan saat tidak bisa login dengan kredensial default (admin/admin).
 * Memperbaiki 3 kemungkinan penyebab:
 *   1. Tabel admin_users tidak ada di database lama
 *   2. Hash password ber-prefix $2b$ (bcrypt Node.js) yang tidak didukung
 *      password_verify() PHP (hanya $2a$/$2y$)
 *   3. Rate limit login yang mengunci IP/username
 *
 * CARA JALANKAN (wajib via CLI):
 *   php tools/reset_admin_password.php
 *
 * AMAN: idempotent, hanya menyentuh tabel admin_users + rate_limit_attempts,
 * tidak mengubah data lain. Akses via web diblokir (HTTP 403).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: jalankan hanya via CLI (php tools/reset_admin_password.php)');
}

require_once __DIR__ . '/../config/database.php';

echo "=== Reset Akun Admin - BAAK Politeknik Nest ===\n\n";

try {
    $pdo = getDbConnection();

    // 1) Pastikan tabel admin_users ada (database lama mungkin belum punya)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "[1/4] OK - tabel admin_users dipastikan ada.\n";

    // 2) Konversi hash $2b$ -> $2y$ (kompatibel password_verify PHP)
    $stmt = $pdo->prepare(
        "UPDATE admin_users
         SET password = CONCAT('$2y$', SUBSTRING(password, 5))
         WHERE password LIKE '$2b$%'"
    );
    $stmt->execute();
    $fixed = $stmt->rowCount();
    if ($fixed > 0) {
        echo "[2/4] OK - {$fixed} hash \$2b\$ dikonversi ke \$2y\$.\n";
    } else {
        echo "[2/4] OK - tidak ada hash \$2b\$ yang perlu dikonversi.\n";
    }

    // 3) Reset akun admin -> admin/admin (hash baru di-generate PHP saat ini)
    $hash = password_hash('admin', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        "INSERT INTO admin_users (username, password) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE password = ?"
    );
    $stmt->execute(['admin', $hash, $hash]);
    echo "[3/4] OK - akun admin direset dengan password default.\n";

    // 4) Bersihkan rate limit pada endpoint login
    //    (tabel mungkin belum ada di database lama — tangani dengan aman)
    try {
        $stmt = $pdo->prepare("DELETE FROM rate_limit_attempts WHERE endpoint = 'login' OR endpoint LIKE 'login:%'");
        $stmt->execute();
        echo "[4/4] OK - rate limit login dibersihkan ({$stmt->rowCount()} baris).\n";
    } catch (Throwable $e) {
        echo "[4/4] SKIP - tabel rate_limit_attempts belum ada (tidak masalah, login tetap berfungsi).\n";
    }

    echo "\n=== SELESAI ===\n";
    echo "Username : admin\n";
    echo "Password : admin\n";
    echo "\nPENTING:\n";
    echo "- Segera ganti password setelah berhasil login.\n";
    echo "- Fitur ganti password belum tersedia di aplikasi; ganti hash manual\n";
    echo "  atau koordinasikan dengan pengembang.\n";
    echo "- Ulangi tool ini jika sewaktu-waktu terkunci rate limit.\n";
    echo "\nJika nama database Anda BUKAN 'polinest_baak', jalankan dengan env vars:\n";
    echo "  DB_NAME=nama_db_kamu php tools/reset_admin_password.php\n";

} catch (Throwable $e) {
    echo "\nGAGAL: " . $e->getMessage() . "\n";
    echo "Cek koneksi database (env DB_HOST/DB_NAME/DB_USER/DB_PASS di config/database.php).\n";
    exit(1);
}
