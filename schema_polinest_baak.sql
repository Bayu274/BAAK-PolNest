-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2026 at 11:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `polinest_baak`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `is_active`, `last_login_at`, `created_at`) VALUES
(1, 'admin', NULL, '$2y$10$VfWo8s.qIQ6wXp9ngY6Sk.QkTNpoEIwJcfuLh0oA3fIcRILAO70LS', 1, '2026-09-02 09:44:45', '2026-07-10 07:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `downloadable_files`
--

CREATE TABLE `downloadable_files` (
  `id` int(11) NOT NULL,
  `file_category` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `downloadable_files`
--

INSERT INTO `downloadable_files` (`id`, `file_category`, `file_name`, `title`, `file_path`, `is_active`, `uploaded_by`, `uploaded_at`) VALUES
(1, 'jadwal_kuliah', 'JADWAL KULIAH.pdf', NULL, 'doc_595c8f8c9bfd478a.pdf', 1, 1, '2026-08-07 08:02:21'),
(2, 'kalender_akademik', 'KALENDER AKADEMIK.pdf', NULL, 'doc_97b6983a659fbe52.pdf', 1, 1, '2026-08-07 08:02:40'),
(3, 'formulir_krs', 'FORMULIR KRS.pdf', NULL, 'doc_7429e0e069ff2330.pdf', 1, 1, '2026-08-07 08:02:49'),
(4, 'sop_dokumen', 'SOP DOKUMEN.pdf', NULL, 'doc_a8c0b6910e0b7286.pdf', 1, 1, '2026-08-07 08:02:59'),
(7, 'panduan_ta', 'PANDUAN TA.pdf', 'PANDUAN TA', 'doc_1aba42cc2221975c.pdf', 1, 1, '2026-08-21 06:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `content`, `thumbnail_image`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES
(6, 'Pendaftaran Wisuda', 'pendaftaran-wisuda', '<p>Daftar wisuda disini</p>', '/storage/uploads/thumb_a04ce802d58ca784.png', 1, '2026-07-13 03:47:59', '2026-08-03 09:15:33', 1),
(7, 'Pendaftaran Magang Industri', 'pendaftaran-magang-industri', '<p>Daftar Magang disini</p>', '/storage/uploads/thumb_e03f3615a3b26106.jpg', 1, '2026-07-13 04:53:35', '2026-08-03 09:15:26', 1),
(8, 'Pendaftaran Kuliah Kerja Nyata', 'pendaftaran-kuliah-kerja-nyata', '<p>Daftar KKN disini</p>', '/storage/uploads/thumb_424e9db489df6237.png', 1, '2026-07-13 05:14:43', '2026-08-03 09:15:18', 1),
(9, 'Pendaftaran Magang Mandiri', 'pendaftaran-magang-mandiri-2', '<p>Daftar Magang Mandiri Disini</p>', '/storage/uploads/thumb_7db2dd38adce32ff.jpg', 1, '2026-07-17 08:26:01', '2026-08-03 09:15:09', 1),
(10, 'Tes', 'tes', '<p>Tes</p>', NULL, 1, '2026-08-07 09:13:50', '2026-08-07 09:13:50', 1),
(11, 'Pendaftaran Seminar Proposal', 'pendaftaran-seminar-proposal', '<h4>DAFTAR SEMINAR DISINI<br />link</h4><p>link<a href=\"https://github.com/Bayu274/BAAK-PolNest/tree/development\" target=\"_blank\" rel=\"noopener noreferrer\">https://github.com/Bayu274/BAAK-PolNest/tree/development</a></p>', '/storage/uploads/thumb_c2bd8d0d8a69f0a5.jpg', 1, '2026-08-21 06:46:45', '2026-08-21 06:46:45', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pages_content`
--

CREATE TABLE `pages_content` (
  `id` int(11) NOT NULL,
  `page_identifier` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `html_content` longtext DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages_content`
--

INSERT INTO `pages_content` (`id`, `page_identifier`, `title`, `html_content`, `updated_by`, `last_updated`) VALUES
(1, 'sop-cuti', NULL, '<p>tes fitur sop cuti</p>', 1, '2026-07-22 04:57:45'),
(2, 'visi-misi', 'Visi Misi', '<p>Visi Misi</p>', 1, '2026-07-22 04:57:55'),
(4, 'visi-misi-ti', 'Visi misi prodi TI', '<h2>TES 1,2,3,</h2>', 1, '2026-08-21 06:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit_attempts`
--

CREATE TABLE `rate_limit_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit_attempts`
--

INSERT INTO `rate_limit_attempts` (`id`, `ip_address`, `endpoint`, `attempt_count`, `window_start`) VALUES
(51, '::1', 'login', 1, '2026-09-02 09:44:45'),
(52, '::1', 'login:admin', 1, '2026-09-02 09:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `student_advisors`
--

CREATE TABLE `student_advisors` (
  `id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `advisor_name` varchar(255) NOT NULL,
  `advisor_type` enum('Wali','Magang','TA') NOT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_advisors`
--

INSERT INTO `student_advisors` (`id`, `nim`, `student_name`, `advisor_name`, `advisor_type`, `imported_at`) VALUES
(1, '21010001', 'andi wijaya', 'Bpk. Bambang Permana M.Pd', 'Wali', '2026-08-03 08:02:41'),
(2, '21010001', 'andi wijaya', 'Ibu Sari Wulandari M.Kom', 'Magang', '2026-08-03 08:02:41'),
(3, '21010001', 'andi wijaya', 'Bpk. Joko Susilo M.T', 'TA', '2026-08-03 08:02:41'),
(4, '21010002', 'siti rahmawati', 'Ibu Dewi Lestari M.Pd', 'Wali', '2026-08-03 08:02:41'),
(5, '21010002', 'siti rahmawati', 'Bpk. Agus Prasetyo M.Kom', 'Magang', '2026-08-03 08:02:41'),
(6, '21010003', 'bagus prasetyo', 'Bpk. Eko Nugroho M.T', 'Wali', '2026-08-03 08:02:41'),
(7, '21010003', 'bagus prasetyo', 'Ibu Ratna Sari S.Kom', 'Magang', '2026-08-03 08:02:41'),
(8, '21010003', 'bagus prasetyo', 'Bpk. Dedi Kurniawan M.Pd', 'TA', '2026-08-03 08:02:41'),
(9, '21010004', 'dewi anggraini', 'Ibu Fitri Handayani M.Pd', 'Wali', '2026-08-03 08:02:41'),
(10, '21010005', 'budi hartanto', 'Bpk. Rudi Setiawan M.T', 'Wali', '2026-08-03 08:02:41'),
(11, '21010006', 'maya puspita', 'Ibu Sri Rahayu M.Kom', 'Wali', '2026-08-03 08:02:41'),
(12, '21010006', 'maya puspita', 'Bpk. Hendra Gunawan S.T', 'Magang', '2026-08-03 08:02:41'),
(13, '21010007', 'rizky ramadhan', 'Bpk. Ahmad Fauzi M.T', 'Wali', '2026-08-03 08:02:41'),
(14, '21010008', 'nur aini', 'Ibu Lina Marlina M.Pd', 'Wali', '2026-08-03 08:02:41'),
(15, '21010009', 'fajar nugraha', 'Bpk. Wisnu Wardana S.Kom', 'Wali', '2026-08-03 08:02:41'),
(16, '21010010', 'linda kusuma', 'Ibu Rina Wahyuni M.Pd', 'Wali', '2026-08-03 08:02:41'),
(17, '21010010', 'linda kusuma', 'Bpk. Bayu Pratama M.Kom', 'Magang', '2026-08-03 08:02:41'),
(18, '21010010', 'linda kusuma', 'Ibu Nadia Rahma S.T', 'TA', '2026-08-03 08:02:41'),
(19, '21010011', 'muhammad iqbal', 'Bpk. Yusuf Hakim M.Kom', 'Wali', '2026-08-03 08:02:41'),
(20, '21010012', 'dian kartika', 'Ibu Putri Ayu Lestari S.Kom', 'Wali', '2026-08-03 08:02:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_active` (`file_category`,`is_active`),
  ADD KEY `fk_files_admin` (`uploaded_by`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `pages_content`
--
ALTER TABLE `pages_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_identifier` (`page_identifier`),
  ADD KEY `fk_pages_admin` (`updated_by`);

--
-- Indexes for table `rate_limit_attempts`
--
ALTER TABLE `rate_limit_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_endpoint` (`ip_address`,`endpoint`);

--
-- Indexes for table `student_advisors`
--
ALTER TABLE `student_advisors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nim_student_name` (`nim`,`student_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pages_content`
--
ALTER TABLE `pages_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rate_limit_attempts`
--
ALTER TABLE `rate_limit_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `student_advisors`
--
ALTER TABLE `student_advisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  ADD CONSTRAINT `fk_files_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pages_content`
--
ALTER TABLE `pages_content`
  ADD CONSTRAINT `fk_pages_admin` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
