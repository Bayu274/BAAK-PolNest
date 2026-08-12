<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

if (!function_exists('ensureAppReady')) {
    /**
     * Auto-setup satu kali: memastikan database dan seluruh tabel siap dipakai,
     * serta akun admin tersedia — TANPA perlu import manual di phpMyAdmin.
     *
     * Alur (idempotent, aman dijalankan tiap request):
     *   1. Buat database bila belum ada (CREATE DATABASE IF NOT EXISTS).
     *   2. Jika tabel admin_users belum ada, buat seluruh tabel (skema sama
     *      dengan schema_polinest_baak.sql) dan seed akun admin/admin
     *      (hash bcrypt di-generate saat runtime, bukan hash mati dari dump).
     *   3. Bila tabel sudah ada, tidak melakukan apa pun (data tidak pernah
     *      ditimpa, password admin yang sudah diganti tidak disentuh).
     */
    function ensureAppReady(): void {
        static $done = false;
        if ($done) {
            return;
        }

        $host     = getenv('DB_HOST') ?: '127.0.0.1';
        $dbname   = getenv('DB_NAME') ?: 'polinest_baak';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?? '';

        try {
            // 1. Pastikan database ada (koneksi tanpa memilih database)
            $server = new PDO(
                "mysql:host={$host};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_TIMEOUT => 5]
            );
            $server->exec(
                "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

            // 2. Cek apakah instalasi sudah pernah di-setup
            $db = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

            if (in_array('admin_users', $tables, true)) {
                // Instalasi lama: pastikan kolom tambahan (email, is_active,
                // last_login_at) dan news.is_active ada — idempotent, tanpa
                // menyentuh data. Kolom yang sudah ada tidak diubah.
                ensureAppSchemaColumns($db);
                // Perbaiki kolom PRIMARY KEY yang tidak AUTO_INCREMENT
                // (instalasi lama: id selalu 0 → upload/impor gagal,
                //  dan todos delete "ID File tidak valid").
                ensureTableAutoIncrement($db);
                $done = true;
                return;
            }

            // 3. Instalasi baru — buat tabel (skema identik schema_polinest_baak.sql)
            $schema = [
                "CREATE TABLE `admin_users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `email` varchar(100) DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                    `is_active` tinyint(1) NOT NULL DEFAULT 1,
                    `last_login_at` timestamp NULL DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE `downloadable_files` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `file_category` varchar(100) NOT NULL,
                    `file_name` varchar(255) NOT NULL,
                    `title` varchar(255) DEFAULT NULL,
                    `file_path` varchar(255) NOT NULL,
                    `is_active` tinyint(1) NOT NULL DEFAULT 1,
                    `uploaded_by` int(11) DEFAULT NULL,
                    `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_category_active` (`file_category`,`is_active`),
                    KEY `fk_files_admin` (`uploaded_by`),
                    CONSTRAINT `fk_files_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE `news` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `title` varchar(255) NOT NULL,
                    `slug` varchar(255) NOT NULL,
                    `content` longtext NOT NULL,
                    `thumbnail_image` varchar(255) DEFAULT NULL,
                    `created_by` int(11) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    `is_active` tinyint(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `idx_created_at` (`created_at`),
                    KEY `idx_news_created_by` (`created_by`),
                    CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE `pages_content` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `page_identifier` varchar(100) NOT NULL,
                    `title` varchar(255) NOT NULL DEFAULT '',
                    `html_content` longtext DEFAULT NULL,
                    `updated_by` int(11) DEFAULT NULL,
                    `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `page_identifier` (`page_identifier`),
                    KEY `fk_pages_admin` (`updated_by`),
                    CONSTRAINT `fk_pages_admin` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE `rate_limit_attempts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ip_address` varchar(45) NOT NULL,
                    `endpoint` varchar(255) NOT NULL,
                    `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
                    `attempt_count` int(11) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_ip_endpoint_window` (`ip_address`,`endpoint`,`window_start`),
                    KEY `idx_window_start` (`window_start`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE `student_advisors` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nim` varchar(20) NOT NULL,
                    `student_name` varchar(255) NOT NULL,
                    `advisor_name` varchar(255) NOT NULL,
                    `advisor_type` enum('Wali','Magang','TA') NOT NULL,
                    `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_nim_type` (`nim`,`advisor_type`),
                    KEY `idx_nim_student_name` (`nim`,`student_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ];

            foreach ($schema as $sql) {
                $db->exec($sql);
            }

            // Seed akun admin (hash bcrypt baru). INSERT IGNORE: tidak menimpa
            // akun admin yang sudah ada/berganti password.
            $stmt = $db->prepare(
                "INSERT IGNORE INTO `admin_users` (`username`, `password`) VALUES ('admin', :hash)"
            );
            $stmt->execute(['hash' => password_hash('admin', PASSWORD_BCRYPT)]);

            $done = true;
            logInfo('Auto-setup selesai: database, tabel, dan akun admin dibuat.');
        } catch (Throwable $e) {
            error_log('Auto-setup gagal: ' . $e->getMessage());
            // Tidak melempar — alur aplikasi tetap berjalan; getDbConnection()
            // akan menampilkan pesan error yang ramah jika database tidak ada.
        }
    }
}

if (!function_exists('ensureAppSchemaColumns')) {
    /**
     * Auto-upgrade skema untuk instalasi lama: menambahkan kolom baru bila belum
     * ada (idempotent, tidak menyentuh data). Kolom yang gagal ditambahkan tidak
     * menghentikan aplikasi — hanya dicatat ke error log.
     */
    function ensureAppSchemaColumns(PDO $db): void {
        $dbname = $db->query('SELECT DATABASE()')->fetchColumn();

        $needed = [
            'admin_users' => [
                'email'         => "ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `username`",
                'is_active'     => "ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `password`",
                'last_login_at' => "ADD COLUMN `last_login_at` timestamp NULL DEFAULT NULL AFTER `is_active`",
            ],
            'news' => [
                'is_active' => "ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `updated_at`",
            ],
            'downloadable_files' => [
                'file_category' => "ADD COLUMN `file_category` varchar(100) NOT NULL AFTER `id`",
                'file_name'     => "ADD COLUMN `file_name` varchar(255) NOT NULL AFTER `file_category`",
                'title'         => "ADD COLUMN `title` varchar(255) DEFAULT NULL AFTER `file_name`",
                'file_path'     => "ADD COLUMN `file_path` varchar(255) NOT NULL AFTER `title`",
                'is_active'     => "ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `file_path`",
                'uploaded_by'   => "ADD COLUMN `uploaded_by` int(11) DEFAULT NULL AFTER `is_active`",
                'uploaded_at'   => "ADD COLUMN `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `uploaded_by`",
            ],
        ];

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );

        foreach ($needed as $table => $columns) {
            foreach ($columns as $column => $alter) {
                try {
                    $stmt->execute([$dbname, $table, $column]);
                    if ((int) $stmt->fetchColumn() === 0) {
                        $db->exec("ALTER TABLE `{$table}` {$alter}");
                        logInfo("Auto-upgrade skema: kolom {$table}.{$column} ditambahkan.");
                    }
                } catch (Throwable $e) {
                    error_log("Auto-upgrade skema gagal ({$table}.{$column}): " . $e->getMessage());
                }
            }
        }
    }
}

if (!function_exists('ensureTableAutoIncrement')) {
    /**
     * Auto-fix untuk instalasi lama yang kolom PRIMARY KEY-nya tidak
     * AUTO_INCREMENT (mis. tabel dibuat oleh setup versi awal atau impor
     * dump manual tanpa bagian MODIFY ... AUTO_INCREMENT).
     *
     * Akibat bila tidak diperbaiki: INSERT tanpa id eksplisit selalu memakai
     * id = 0 → baris pertama tersimpan dengan id 0, baris berikutnya gagal
     * "Duplicate entry '0' for key 'PRIMARY'" (upload/impor CSV mogok), dan
     * semua operasi berbasis id (hapus/unduh) menolak id 0.
     *
     * Alur (idempotent, aman dijalankan tiap request):
     *   1. Cek via INFORMATION_SCHEMA apakah kolom id sudah auto_increment.
     *   2. Bila belum: beri id unik berurutan ke semua baris (menggantikan
     *      nilai 0 yang tersimpan), lalu MODIFY menjadi AUTO_INCREMENT.
     *      Tidak menghapus/mengubah data lain.
     */
    function ensureTableAutoIncrement(PDO $db): void {
        $tables = ['admin_users', 'downloadable_files', 'news', 'pages_content', 'rate_limit_attempts', 'student_advisors'];

        foreach ($tables as $table) {
            try {
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'id' AND EXTRA LIKE '%auto_increment%'"
                );
                $stmt->execute([$table]);

                if ((int) $stmt->fetchColumn() > 0) {
                    continue; // sudah benar
                }

                // Beri id unik berurutan dulu (row id=0 yang tersimpan di masa
                // lalu bisa jadi tidak unik/aneh — urutkan dari id lama).
                $db->exec("SET @baak_rn := 0;");
                $db->exec("UPDATE `{$table}` SET `id` = (@baak_rn := @baak_rn + 1) ORDER BY `id`");
                $db->exec("ALTER TABLE `{$table}` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");

                logInfo("Auto-fix: {$table}.id tidak AUTO_INCREMENT — sudah diperbaiki.");
            } catch (Throwable $e) {
                error_log("ensureTableAutoIncrement gagal ({$table}): " . $e->getMessage());
            }
        }
    }
}
