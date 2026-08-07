-- Migration 002: Tambah kolom `title` ke tabel `pages_content`
-- Alasan: Page::create() dan pages-list.php sudah menggunakan kolom `title`,
--          tapi schema asli belum memilikinya.
-- Idempotent: aman dijalankan berulang kali (cek keberadaan kolom dulu).

SET @dbname = DATABASE();
SET @tablename = 'pages_content';
SET @columnname = 'title';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `page_identifier`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
