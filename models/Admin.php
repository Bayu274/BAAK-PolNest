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
}