# PRD — Portal Informasi BAAK (Biro Administrasi & Akademik Kampus)
**Politeknik Nest, Sukoharjo**

**Versi:** 3.1 — Draft Internal (belum dikirim ke klien)
**Update terakhir:** 6 Juli 2026
**PIC Klien:** Pak Dimas Pamilih
**Peran Dokumen:** Dasar persetujuan scope bersama klien + blueprint teknis untuk tim development

---

## 1. Status Dokumen

- 🔴 **BELUM FINAL** — draft ini belum dikonfirmasi/dikirim ke pihak BAAK Politeknik NEST.
- Ada **1 open question** yang wajib dijawab klien sebelum modul pencarian dosen dikerjakan → **Bagian 5**.
- Bagian 9–13 (arsitektur teknis) bersifat internal tim. Kalau dokumen ini dikirim resmi ke Pak Dimas, bagian tersebut sebaiknya dipisah/dihapus dari versi client-facing.
- Dokumen ini = acuan tunggal untuk development. Jangan menambah fitur di luar scope tanpa update di sini dulu (lihat **Bagian 4**).

## 2. Konteks & Jenis Sistem

- **Jenis sistem:** CMS / Portal Informasi Publik. **BUKAN** sistem transaksional (bukan SIAKAD).
- Referensi pembanding: BAAK Universitas Gunadarma.
- Masalah yang diselesaikan:
  1. **Sentralisasi formulir & SOP** — mahasiswa sebelumnya kesulitan mencari format form (Cuti, Pindah Kelas, Mengundurkan Diri) dan prosedurnya.
  2. **Privasi data mahasiswa** — NIM & Nama tidak dipublikasikan terbuka; harus lewat mekanisme pencarian tertutup.
  3. **Kemudahan update konten** — Admin BAAK (non-programmer) bisa update berita/konten/file sendiri, tanpa hardcode.

## 3. Keputusan Final (Sudah Dikonfirmasi Klien)

- ✅ Sistem = portal informasi (CMS), bukan sistem transaksional.
- ✅ Form **Cuti**, **Pindah Kelas**, **Rencana Studi (KRS)** = diunduh mahasiswa → diisi manual → diserahkan fisik ke kantor BAAK. **Tidak ada submit/approval online** untuk ketiganya.
- ✅ PIC klien: **Pak Dimas Pamilih**.

## 4. Batasan Eksplisit — Jangan Dibangun

> Supaya AI/dev tidak menambah fitur "yang kelihatannya logis" tapi di luar scope.

- ❌ Jangan bangun workflow submit/approval online untuk Cuti, Pindah Kelas, atau KRS — ketiganya **download-only**.
- ❌ Jangan pakai framework PHP (Laravel, CodeIgniter, dll.) — stack wajib **PHP Native + MySQL** (Bagian 9).
- ❌ NIM & Nama mahasiswa **tidak boleh** ditampilkan/searchable terbuka tanpa mekanisme pencarian tertutup (Bagian 5).
- ⏸️ Jangan finalisasi logic modul pencarian dosen sebelum **Open Question (Bagian 5)** dijawab klien.

## 5. 🔴 Open Question — Wajib Dijawab Klien

**Q1 — Mekanisme pencarian dosen pembimbing:** NIM saja, atau NIM + nama lengkap?

- **Rekomendasi tim: NIM + nama.** NIM cenderung berpola/berurutan → pencarian berbasis NIM saja rawan di-*enumerate* otomatis untuk menarik seluruh data dosen-mahasiswa, bertentangan dengan tujuan privasi (Bagian 2).
- Kalau klien tetap pilih NIM saja: risiko keamanan ini jadi keputusan sadar bersama, dicatat tertulis — bukan default diam-diam dari tim.
- Jawaban wajib tertulis (email/WhatsApp/tanda tangan dokumen) dan akan dilampirkan sebagai dokumentasi resmi proyek + laporan Praktik Industri.

> ⚠️ **Inkonsistensi di dokumen asli — cek ke penulis PRD sebelum dipakai jadi acuan:** bagian "Cakupan Fitur" PRD asli menyebut *"mekanisme verifikasi tambahan menunggu jawaban Q3"*, tapi tabel pertanyaan resmi cuma berisi 1 baris (**Q1**), dan bagian "Pertanyaan untuk Klien" menyebut *"satu poin"* (tunggal). Kemungkinan draft sebelum v3.1 punya beberapa pertanyaan yang lalu disederhanakan jadi 1 di v3.1, tapi referensi lama ("Q3") tidak ikut diperbarui. **This needs verification** ke Dhafin/Ridwan — pastikan memang cuma 1 pertanyaan.

## 6. Keputusan Tim — Default MVP (Belum Dikonfirmasi Tertulis Klien)

> ⚠️ Kalau klien nanti minta salah satu dari 4 item ini jadi data terstruktur (bisa dicari/difilter), perlu tabel database baru + fitur pencarian baru — di luar rancangan saat ini. Infokan ke Pak Dimas sebelum dianggap final.

4 item berikut ditampilkan sebagai **PDF unduhan yang dikelola admin** — bukan data terstruktur:
- Daftar MK per Prodi
- Koordinator MK
- Jadwal Seminar (Magang/Sempro/TA/Ujian TA)
- Jadwal Pelayanan BAAK

## 7. Requirement Coverage Matrix

| No | Kebutuhan Awal BAAK NEST | Status | Catatan |
|----|--------------------------|--------|---------|
| 1 | Kalender Akademik | Tercakup | Unduhan PDF, dikelola admin |
| 2 | Daftar MK per Prodi | Tercakup | Unduhan PDF — keputusan tim (Bagian 6) |
| 3 | Daftar Dosen Wali Kelas (PA) | Tercakup | Pencarian berbasis NIM (Bagian 5) |
| 4 | Koordinator MK | Tercakup | Unduhan PDF — keputusan tim (Bagian 6) |
| 5 | Daftar Dosen PI (Pembimbing Magang) | Tercakup | Pencarian berbasis NIM (Bagian 5) |
| 6 | Daftar Dosen Pembimbing TA | Tercakup | Pencarian berbasis NIM (Bagian 5) |
| 7 | Daftar Kuliah, UTS, UAS | Tercakup | Unduhan PDF, dikelola admin |
| 8 | Jadwal Seminar Magang/Sempro/TA/Ujian TA | Tercakup | Unduhan PDF — keputusan tim (Bagian 6) |
| 9 | Form Rencana Studi (KRS) | Tercakup | Template unduhan, bukan transaksional |
| 10 | Form Cuti | Tercakup | Template unduhan |
| 11 | Form Mengundurkan Diri | Tercakup | Template unduhan |
| 12 | Situs RPS (link) | Tercakup | Tautan keluar dari halaman Jadwal & Pedoman |
| 13 | Buku Pedoman Akademik, Panduan Magang, TA | Tercakup | Unduhan PDF, dikelola admin |
| 14 | Jadwal Pelayanan BAAK | Tercakup | Unduhan PDF/halaman statis — keputusan tim (Bagian 6) |
| 15 | Form Pindah Kelas | Tercakup | Template unduhan |

## 8. User Roles & Hak Akses

**Admin (Staf BAAK)** — perlu login:
- Akses penuh backend: CRUD berita, upload/overwrite PDF, edit konten halaman (rich text editor), import CSV data dosen pembimbing.

**Publik (mahasiswa & dosen)** — tanpa login:
- Baca berita/info BAAK, cari dosen pembimbing (NIM ± nama — Bagian 5), download PDF (kalender, jadwal, template form).

## 9. Tech Stack & Struktur Folder

**Stack:** PHP Native (tanpa framework), HTML, CSS, JavaScript, MySQL.

```
/config       → koneksi database, env vars, base_url
/controllers  → handle form submit, proses import CSV, logika CRUD
/models       → query SELECT / INSERT / UPDATE / DELETE
/views        → split /frontend dan /backend; shared parts → header.php, footer.php, sidebar.php
/assets       → CSS, JS, Bootstrap lokal, gambar, file upload
```

## 10. Feature Scope

### 10A. Frontend (Publik)
- **Beranda** — banner kampus + feed berita/pengumuman terkini
- **Halaman Info Pelayanan** — teks SOP (pendaftaran, cuti, pindah kelas, dll.) + tombol download template form (Word/PDF)
- **Halaman Jadwal & Pedoman** — download Kalender Akademik, Jadwal Kuliah, Jadwal Ujian, RPS (link keluar), Buku Pedoman
- **Modul Pencarian Dosen** (Wali/PI/Pembimbing TA) — search by NIM ± nama (Bagian 5), AJAX/Fetch API, hasil instan tanpa reload
- 4 item Bagian 6 — tampil sebagai download PDF

### 10B. Backend (Dashboard Admin)
- **Autentikasi** — login admin, password wajib hash (bcrypt / `password_hash()`)
- **CRUD Berita** — tambah, edit, hapus, publikasi artikel
- **Manajemen Konten Halaman** — rich text editor (TinyMCE/CKEditor), edit teks SOP tanpa sentuh HTML
- **Manajemen File** — upload/overwrite PDF; **simpan histori file lama**, jangan cuma overwrite langsung
- **Import CSV Data Pembimbing** — upload Excel/CSV (relasi NIM–Nama–Dosen–Jenis); **validasi penuh dulu** sebelum truncate + replace data lama

## 11. Security Requirements (Checklist Wajib)

- [ ] **SQL Injection** — semua query WAJIB Prepared Statements (PDO/MySQLi). Dilarang string concatenation ke query, tanpa pengecualian.
- [ ] **XSS** — `htmlspecialchars()` di semua output teks dari database.
- [ ] **Akses Backend** — cek `$_SESSION` di tiap file backend, redirect ke Login kalau sesi tidak aktif. *(Tambahan di luar PRD asli: pakai satu file guard/include terpusat yang wajib di-include di tiap halaman backend, supaya tidak ada file yang lupa dikasih pengecekan satu-satu.)*
- [ ] **CSRF** — token CSRF di semua form Dashboard Admin (upload file, import CSV, publikasi berita).
- [ ] Password admin wajib di-hash — tidak boleh plaintext.
- [ ] Modul pencarian dosen — implementasi final tunggu jawaban **Open Question (Bagian 5)**.

## 12. UI/UX

- Bootstrap 5 (atau versi stabil lain) — responsive/mobile-friendly.
- AJAX/Fetch API untuk pencarian dosen — hasil instan tanpa reload halaman.

## 13. Database Schema (MySQL)

| Tabel | Kolom Utama | Catatan |
|-------|-------------|---------|
| `admin_users` | `id`, `username`, `password` (hash) | Wajib hash bcrypt/`password_hash()` |
| `news` | `id`, `title`, `slug`, `content`, `thumbnail_image`, `created_at` | `slug` unik untuk URL SEO-friendly |
| `pages_content` | `id`, `page_identifier` (unik), `html_content`, `last_updated` | Tidak ada version history — perubahan overwrite langsung |
| `downloadable_files` | `id`, `file_category`, `file_name`, `file_path` | Disarankan simpan histori file lama |
| `student_advisors` | `id`, `nim` (index), `student_name`, `advisor_name`, `advisor_type` (Wali/Magang/TA) | TRUNCATE + reload tiap semester dari CSV baru — validasi penuh sebelum truncate |

## 14. Approval Klien

- [ ] Nama: ______________________
- [ ] Jabatan: ______________________
- [ ] Tanda tangan & tanggal: ______________________
