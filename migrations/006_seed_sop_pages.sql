-- Migration 006: Seed halaman SOP Cuti Akademik
-- ================================================================
-- Latar belakang: seed lama di schema_polinest_baak.sql masih berisi
-- placeholder "<p>TES FITUR&nbsp;</p>". Migration ini mengisi konten
-- SOP Cuti Akademik template (definisi, syarat, prosedur, berkas).
--
-- AMAN dijalankan berulang kali:
--   * INSERT IGNORE by page_identifier (UNIQUE) -> jika halaman sudah
--     ada (di-edit admin / di-seed), baris TIDAK ditimpa.
--   * Tidak menghapus / mengubah data lain.
--
-- Catatan: isi resmi SOP final menyusul dari klien (Pak Dimas). Konten
-- di bawah adalah template struktur standar yang bisa disunting admin
-- via /admin/pages/edit/sop-cuti.

INSERT IGNORE INTO `pages_content` (`page_identifier`, `title`, `html_content`) VALUES
('sop-cuti', 'SOP Cuti Akademik',
'<h2>Apa itu Cuti Akademik?</h2><p>Cuti Akademik adalah penangguhan sementara kegiatan perkuliahan oleh mahasiswa untuk jangka waktu tertentu dengan tetap mempertahankan status kemahasiswaannya, sesuai ketentuan yang berlaku di Politeknik Nest.</p><h2>Persyaratan Umum</h2><ul><li>Terdaftar sebagai mahasiswa aktif pada semester berjalan.</li><li>Tidak sedang menjalani sanksi akademik atau administrasi.</li><li>Mengajukan permohonan paling lambat sesuai jadwal yang ditetapkan BAAK.</li><li>Melunasi kewajiban administrasi yang berlaku.</li></ul><h2>Prosedur Pengajuan</h2><ol><li>Mahasiswa mengunduh Formulir Cuti pada halaman <a href="/jadwal">Jadwal &amp; Pedoman</a>.</li><li>Mengisi formulir secara lengkap dan menandatanganinya.</li><li>Melampirkan berkas pendukung sesuai daftar di bawah.</li><li>Menyerahkan berkas ke loket pelayanan BAAK pada jam kerja.</li><li>Menunggu verifikasi dan pengesahan dari bagian akademik.</li></ol><h2>Berkas yang Dibutuhkan</h2><ul><li>Formulir Cuti yang sudah diisi.</li><li>Fotokopi Kartu Rencana Studi (KRS) semester terakhir.</li><li>Surat keterangan atau dokumen pendukung alasan cuti.</li></ul><h2>Informasi Lebih Lanjut</h2><p>Untuk pertanyaan lebih lanjut silakan hubungi loket pelayanan BAAK pada jam kerja (Senin&ndash;Jumat, 08.00&ndash;15.00 WIB) atau melalui email <a href="mailto:baak@politekniknest.ac.id">baak@politekniknest.ac.id</a>.</p>'
);
