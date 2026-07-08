-- ============================================================
-- Portal Informasi BAAK — Politeknik Nest
-- Database Schema (MySQL / InnoDB) — v2
-- Referensi: Struktur_Database.txt + PRD.md v4.0
--
-- CHANGELOG dari versi sebelumnya:
-- - rate_limit_attempts: dikonfirmasi jalan (bukan lagi opsional)
-- - student_advisors.imported_at: dikonfirmasi jalan
-- - [BARU] admin_id ditambahkan ke news, pages_content,
--   downloadable_files untuk audit trail (siapa yang buat/ubah/upload)
-- - [BARU] news.updated_at, pages_content sudah punya last_updated
-- - Semua FK ke admin_users pakai ON DELETE SET NULL — supaya kalau
--   akun admin suatu saat dihapus, konten/berita/file yang sudah
--   dipublikasikan tidak ikut hilang atau gagal delete
-- ============================================================

CREATE DATABASE IF NOT EXISTS polinest_baak
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE polinest_baak;

-- ------------------------------------------------------------
-- 1. admin_users
-- ------------------------------------------------------------
CREATE TABLE admin_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- password_hash() bcrypt
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. news
-- [BARU] created_by: melacak admin mana yang membuat/publish artikel
-- [BARU] updated_at: melacak kapan artikel terakhir diedit
-- ------------------------------------------------------------
CREATE TABLE news (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) NOT NULL UNIQUE,
    content          LONGTEXT     NOT NULL,
    thumbnail_image  VARCHAR(255) DEFAULT NULL,
    created_by       INT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),   -- untuk sorting feed berita terbaru
    CONSTRAINT fk_news_admin FOREIGN KEY (created_by)
        REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. pages_content
-- [BARU] updated_by: melacak admin mana yang terakhir edit halaman
-- ------------------------------------------------------------
CREATE TABLE pages_content (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    page_identifier  VARCHAR(100) NOT NULL UNIQUE,
    html_content     LONGTEXT     DEFAULT NULL,
    updated_by       INT DEFAULT NULL,
    last_updated     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pages_admin FOREIGN KEY (updated_by)
        REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. downloadable_files
-- [BARU] uploaded_by: melacak admin mana yang upload/replace file
-- ------------------------------------------------------------
CREATE TABLE downloadable_files (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    file_category   VARCHAR(100) NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_path       VARCHAR(255) NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    uploaded_by     INT DEFAULT NULL,
    uploaded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_active (file_category, is_active),
    CONSTRAINT fk_files_admin FOREIGN KEY (uploaded_by)
        REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. student_advisors
-- imported_at dikonfirmasi jalan — melacak kapan CSV terakhir di-import,
-- berguna kalau ada laporan data pembimbing salah/usang.
-- ------------------------------------------------------------
CREATE TABLE student_advisors (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nim            VARCHAR(20)  NOT NULL,
    student_name   VARCHAR(255) NOT NULL,
    advisor_name   VARCHAR(255) NOT NULL,
    advisor_type   ENUM('Wali','Magang','TA') NOT NULL,
    imported_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nim_student_name (nim, student_name)   -- composite index wajib (PRD Bagian 5)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. rate_limit_attempts
-- Dikonfirmasi jalan. Session TIDAK dipakai karena mudah di-bypass
-- (buka tab/browser baru). Perlu job pembersih baris lama secara
-- berkala (mis. DELETE WHERE window_start < NOW() - INTERVAL 1 HOUR)
-- supaya tabel ini tidak membengkak tanpa batas — belum ada mekanisme
-- otomatis untuk ini, masih perlu diputuskan (cron job / dibersihkan
-- saat request masuk / event scheduler MySQL).
-- ------------------------------------------------------------
CREATE TABLE rate_limit_attempts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ip_address      VARCHAR(45) NOT NULL,   -- VARCHAR(45) agar muat IPv6
    endpoint        VARCHAR(100) NOT NULL,  -- ex: 'search_advisor'
    attempt_count   INT NOT NULL DEFAULT 1,
    window_start    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data contoh (opsional — ganti hash password sebelum dipakai)
-- ------------------------------------------------------------
-- INSERT INTO admin_users (username, password)
-- VALUES ('admin', '$2y$10$GANTI_DENGAN_HASIL_password_hash()');
