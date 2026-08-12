-- Migration 007: Tambah kolom `title` (judul tampilan) di `downloadable_files`
-- ================================================================
-- Request klien: admin bisa memberikan judul sendiri untuk file unduhan
-- (judul tampilan publik berbeda dari nama file fisik).
--
-- AMAN dijalankan berulang kali (idempotent, pola sama dengan 003/004):
-- cek keberadaan kolom via INFORMATION_SCHEMA, tanpa menyentuh data.

SET @dbname = DATABASE();
SET @tablename = 'downloadable_files';

SET @col = 'title';
SET @prepared = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` varchar(255) DEFAULT NULL AFTER `file_name`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;
