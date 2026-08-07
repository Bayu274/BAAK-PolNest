-- Migration 005: Perbaikan & Verifikasi tabel `downloadable_files`
-- ================================================================
-- Memperbaiki tiga masalah yang dilaporkan (upload mogok, delete ditolak,
-- download 404 padahal baris masih ada) yang berasal dari DATABASE:
--
--   1) Memastikan seluruh kolom yang dipakai kode ada (idempotent, pola
--      sama dgn migrations/004_news_is_active.sql). Schema aktual instalasi
--      lama bisa saja tertinggal dari schema_polinest_baak.sql.
--   2) Mengaktifkan kembali file yang masih bermakna (is_active = 1),
--      supaya tampil di admin & halaman /jadwal.
--   3) Menormalisasi file_path menjadi nama file polos ("doc_x.pdf") —
--      kalau tersimpan dengan prefix path seperti "storage/uploads/doc_x.pdf",
--      scorer admin/manual maupun cleanup otomatis bisa keliru menggapnya
--      dan menghapus file fisik (penyebab "baris ada tapi download 404").
--
-- AMAN dijalankan berulang kali dan TIDAK menghapus data.

SET @dbname = DATABASE();
SET @tablename = 'downloadable_files';

-- 1) Tambah kolom yang hilang (idempotent, per kolom) ---------------------
SET @col = 'file_category';
SET @prepared = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` varchar(100) NOT NULL AFTER `id`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'file_name';
SET @prepared = (SELECT IF(
  COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` varchar(255) NOT NULL AFTER `file_category`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'file_path';
SET @prepared = (SELECT IF(
  COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` varchar(255) NOT NULL AFTER `file_name`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'is_active';
SET @prepared = (SELECT IF(
  COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` tinyint(1) NOT NULL DEFAULT 1 AFTER `file_path`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'uploaded_by';
SET @prepared = (SELECT IF(
  COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` int(11) DEFAULT NULL AFTER `is_active`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = 'uploaded_at';
SET @prepared = (SELECT IF(
  COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @col) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @col, '` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `uploaded_by`')
));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Aktifkan kembali semua file (tampil di admin & /jadwal) ---------------
UPDATE `downloadable_files` SET `is_active` = 1 WHERE `is_active` = 0;

-- 3) Normalisasi file_path jadi nama file polos ---------------------------
UPDATE `downloadable_files`
SET `file_path` = SUBSTRING_INDEX(REPLACE(`file_path`, '\\', '/'), '/', -1)
WHERE INSTR(`file_path`, '/') > 0 OR INSTR(`file_path`, '\\') > 0;

-- 4) PERBAIKAN UTAMA: kolom `id` harus AUTO_INCREMENT -----------------------
-- Instalasi lama bisa saja punya tabel yang kolom id-nya TANPA AUTO_INCREMENT.
-- Efeknya: INSERT tanpa id eksplisit selalu memakai id = 0 → baris pertama
-- tersimpan dengan id 0, baris berikutnya gagal "Duplicate entry '0'". Semua
-- operasi berbasis id (hapus = "ID File tidak valid", unduh = 404) pun gagal.
-- Langkah ini: beri id unik berurutan ke baris lama, lalu MODIFY jadi AUTO_INCREMENT.
SET @hasAI = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'downloadable_files'
                AND COLUMN_NAME = 'id' AND EXTRA LIKE '%auto_increment%');

-- Renumerasi hanya dilakukan bila id belum auto_increment (hindari mengubah
-- transaksi id yang sudah berjalan sehat).
SET @prepared = (SELECT IF(@hasAI > 0, 'SELECT 1', 'UPDATE `downloadable_files` SET `id` = (@rn := @rn + 1) ORDER BY `id`'));
SET @rn := 0;
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @prepared = (SELECT IF(@hasAI > 0, 'SELECT 1',
  'ALTER TABLE `downloadable_files` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT'));
PREPARE stmt FROM @prepared; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Verifikasi hasil -------------------------------------------------------
SELECT `id`, `file_category`, `file_name`, `file_path`, `is_active`, `uploaded_at`
FROM `downloadable_files` ORDER BY `id`;