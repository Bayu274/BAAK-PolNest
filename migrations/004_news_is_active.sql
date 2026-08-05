-- Migration 004: Tambah kolom `is_active` ke tabel `news`
-- Fungsi: mode draft/publikasi — berita dengan is_active = 0 tidak tampil
--         di halaman publik (beranda, katalog /berita, detail berita),
--         tetapi tetap terlihat & bisa diedit di admin.
-- Idempotent: aman dijalankan berulang kali (cek keberadaan kolom dulu,
--             pola sama dengan migrations/002_add_pages_title.sql).

SET @dbname = DATABASE();
SET @tablename = 'news';
SET @columnname = 'is_active';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` tinyint(1) NOT NULL DEFAULT 1 AFTER `updated_at`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;