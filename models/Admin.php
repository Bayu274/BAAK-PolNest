<?php

class Admin
{
    public function findByUsername(string $username): array|false
    {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result ?: false;
    }

    /**
     * Update hash password (dipakai saat auto-upgrade legacy MD5 -> bcrypt)
     */
    public function updatePassword(int $id, string $hash): bool
    {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    /**
     * Catat waktu login terakhir. Aman di database lama yang belum punya
     * kolom last_login_at — kegagalan hanya dicatat, tidak melempar error.
     */
    public function updateLastLogin(int $id): bool
    {
        $pdo = getDbConnection();
        try {
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            error_log("Admin::updateLastLogin gagal (kolom last_login_at belum ada?): " . $e->getMessage());
            return false;
        }
    }
}