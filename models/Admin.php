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
}