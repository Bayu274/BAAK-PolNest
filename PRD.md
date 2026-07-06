# PRD — Portal Informasi BAAK (Biro Administrasi & Akademik Kampus)
**Politeknik Nest, Sukoharjo**

**Versi:** 4.0 — Final / Baku (Baseline Disetujui Klien)
**Update terakhir:** 6 Juli 2026
**PIC Klien:** Pak Dimas Pamilih
**Peran Dokumen:** Baseline resmi scope proyek (disetujui klien) + blueprint teknis untuk tim development

---

## 1. Status Dokumen

- 🟢 **FINAL / BAKU** — seluruh scope & open question sudah dikonfirmasi klien (Pak Dimas Pamilih, via WhatsApp, 6 Juli 2026). Lihat **Bagian 15 (Log Keputusan)** untuk jejak konfirmasinya.
- Satu-satunya open question (**Q1** — modul pencarian dosen) sudah terjawab → **Bagian 5**. Catatan referensi "Q3" di draft v3.1 sudah diverifikasi ke Dhafin: itu sisa referensi basi dari draft sebelumnya, bukan pertanyaan sungguhan — **tidak ada open question lain yang tersisa**.
- Bagian 9–13 (arsitektur teknis) bersifat internal tim. Kalau dokumen ini dikirim resmi ke Pak Dimas, bagian tersebut sebaiknya dipisah/dihapus dari versi client-facing.
- **Mekanisme perubahan scope pasca-final:** lihat **Bagian 5A** — semua penambahan/perubahan wajib lewat evaluasi mingguan (Jumat sore) + update tertulis di dokumen ini, bukan kesepakatan lisan yang tidak tercatat.
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
- ✅ **Modul pencarian dosen pembimbing = NIM + Nama Lengkap**, keduanya wajib cocok bersamaan (mengikuti rekomendasi tim; dikonfirmasi klien via WhatsApp, 6 Juli 2026 — lihat **Bagian 5**).
- ✅ **4 item Bagian 6** (Daftar MK per Prodi, Koordinator MK, Jadwal Seminar, Jadwal Pelayanan BAAK) = **disetujui sebagai PDF unduhan dikelola admin**, ikut rekomendasi tim (lihat **Bagian 6**).
- ✅ **Pendekatan kerja:** untuk keputusan teknis yang sifatnya rekomendasi tim, tim boleh lanjut tanpa menunggu approval tertulis per-item, diawasi lewat evaluasi mingguan bersama klien (lihat **Bagian 5A**).

## 4. Batasan Eksplisit — Jangan Dibangun

> Supaya AI/dev tidak menambah fitur "yang kelihatannya logis" tapi di luar scope.

- ❌ Jangan bangun workflow submit/approval online untuk Cuti, Pindah Kelas, atau KRS — ketiganya **download-only**.
- ❌ Jangan pakai framework PHP (Laravel, CodeIgniter, dll.) — stack wajib **PHP Native + MySQL** (Bagian 9).
- ❌ NIM & Nama mahasiswa **tidak boleh** ditampilkan/searchable terbuka tanpa mekanisme pencarian tertutup — wajib **NIM + Nama Lengkap sekaligus**, bukan salah satu saja (Bagian 5).
- ❌ Jangan terima perubahan scope dari klien secara lisan/informal tanpa dicatat lewat evaluasi Jumat + update tertulis di dokumen ini (Bagian 5A).

## 5. ✅ Modul Pencarian Dosen — Keputusan Final (dulu Open Question Q1)

**Q1 — Mekanisme pencarian dosen pembimbing:** NIM saja, atau NIM + nama lengkap?

- **Rekomendasi tim: NIM + nama.** NIM cenderung berpola/berurutan → pencarian berbasis NIM saja rawan di-*enumerate* otomatis untuk menarik seluruh data dosen-mahasiswa, bertentangan dengan tujuan privasi (Bagian 2).
- **✅ Keputusan final: NIM + Nama Lengkap, keduanya wajib cocok.** Pencarian TIDAK mengembalikan hasil kalau cuma salah satu yang benar.
- **Konfirmasi klien:** Pak Dimas Pamilih, via WhatsApp, 6 Juli 2026. Kutipan lengkap ada di **Bagian 15 (Log Keputusan)**.
- Dicatat sebagai dokumentasi resmi proyek + lampiran laporan Praktik Industri, sesuai requirement dokumentasi di atas.
- ~~Catatan draft v3.1 soal referensi "Q3"~~ — **sudah diverifikasi ke Dhafin: tidak ada pertanyaan lain.** Itu cuma sisa referensi dari draft sebelum v3.1 yang tidak ikut diperbarui saat disederhanakan jadi 1 pertanyaan. Dihapus dari versi ini.

### 🆕 Rekomendasi Hardening Tambahan (usul tim — belum dikonfirmasi klien, bisa dibahas di evaluasi Jumat)
- **Exact match**, bukan partial/`LIKE`, untuk NIM maupun Nama. Normalisasi input (`trim`, lowercase, kompres spasi ganda) sebelum dibandingkan — supaya tetap ketat ke enumerasi tapi toleran ke typo minor (spasi/kapitalisasi user).
- **Rate limiting** di endpoint pencarian (mis. maks. N request/menit per IP) sebagai lapis pertahanan tambahan di luar syarat NIM+Nama itu sendiri — supaya percobaan brute-force otomatis tetap sulit meski sudah butuh 2 field sekaligus.
- **Composite index** `(nim, student_name)` di tabel `student_advisors` untuk performa query (lihat Bagian 13).

## 5A. 🆕 Mekanisme Evaluasi & Governance Proyek

> Bagian baru — hasil konfirmasi klien 6 Juli 2026, mengatur cara kerja tim & mekanisme approval setelah dokumen ini final.

- **Pendekatan kerja:** untuk keputusan teknis yang sifatnya rekomendasi tim (bukan pilihan bisnis/kebijakan institusi), tim lanjut sesuai rekomendasi terbaik tanpa menunggu approval tertulis satu-satu dari klien.
- **Checkpoint mingguan:** evaluasi progres tiap **Jumat sore, sebelum jam pulang**, bersama Pak Dimas — (1) demo/laporan progress, (2) diskusi kalau ada tambahan/perbaikan yang klien perlukan.
- **Kalau ada perubahan scope hasil evaluasi Jumat:** wajib dicatat di dokumen ini (update versi + entri baru di **Bagian 15**), bukan cuma kesepakatan lisan yang tidak terdokumentasi.
- **Batas pendelegasian:** kalau tim ragu apakah sesuatu masih "sesuai rekomendasi tim" atau sudah masuk keputusan bisnis yang butuh persetujuan eksplisit (mis. menyangkut kebijakan institusi, biaya tambahan, perubahan scope besar) → tetap diangkat ke Pak Dimas secara eksplisit, jangan diasumsikan otomatis approved.

## 6. ✅ Keputusan Tim — Default MVP (Dikonfirmasi Klien)

> ⚠️ Kalau klien nanti minta salah satu dari 4 item ini jadi data terstruktur (bisa dicari/difilter), tetap perlu tabel database baru + fitur pencarian baru — di luar rancangan saat ini. Ini scope change baru meskipun keputusan bentuk PDF ini sendiri sudah final; tetap bahas di evaluasi Jumat sebelum dikerjakan.

4 item berikut ditampilkan sebagai **PDF unduhan yang dikelola admin** — bukan data terstruktur. **Disetujui klien** (via WhatsApp, 6 Juli 2026, mengikuti rekomendasi tim — Bagian 5A):
- Daftar MK per Prodi
- Koordinator MK
- Jadwal Seminar (Magang/Sempro/TA/Ujian TA)
- Jadwal Pelayanan BAAK

## 7. Requirement Coverage Matrix

| No | Kebutuhan Awal BAAK NEST | Status | Catatan |
|----|--------------------------|--------|---------|
| 1 | Kalender Akademik | Tercakup | Unduhan PDF, dikelola admin |
| 2 | Daftar MK per Prodi | Tercakup | Unduhan PDF — final, keputusan tim (Bagian 6) |
| 3 | Daftar Dosen Wali Kelas (PA) | Tercakup | Pencarian **NIM + Nama** (final, Bagian 5) |
| 4 | Koordinator MK | Tercakup | Unduhan PDF — final, keputusan tim (Bagian 6) |
| 5 | Daftar Dosen PI (Pembimbing Magang) | Tercakup | Pencarian **NIM + Nama** (final, Bagian 5) |
| 6 | Daftar Dosen Pembimbing TA | Tercakup | Pencarian **NIM + Nama** (final, Bagian 5) |
| 7 | Daftar Kuliah, UTS, UAS | Tercakup | Unduhan PDF, dikelola admin |
| 8 | Jadwal Seminar Magang/Sempro/TA/Ujian TA | Tercakup | Unduhan PDF — final, keputusan tim (Bagian 6) |
| 9 | Form Rencana Studi (KRS) | Tercakup | Template unduhan, bukan transaksional |
| 10 | Form Cuti | Tercakup | Template unduhan |
| 11 | Form Mengundurkan Diri | Tercakup | Template unduhan |
| 12 | Situs RPS (link) | Tercakup | Tautan keluar dari halaman Jadwal & Pedoman |
| 13 | Buku Pedoman Akademik, Panduan Magang, TA | Tercakup | Unduhan PDF, dikelola admin |
| 14 | Jadwal Pelayanan BAAK | Tercakup | Unduhan PDF/halaman statis — final, keputusan tim (Bagian 6) |
| 15 | Form Pindah Kelas | Tercakup | Template unduhan |

## 8. User Roles & Hak Akses

**Admin (Staf BAAK)** — perlu login:
- Akses penuh backend: CRUD berita, upload/overwrite PDF, edit konten halaman (rich text editor), import CSV data dosen pembimbing.

**Publik (mahasiswa & dosen)** — tanpa login:
- Baca berita/info BAAK, cari dosen pembimbing (**NIM + Nama**, keduanya wajib — Bagian 5), download PDF (kalender, jadwal, template form).

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
- **Modul Pencarian Dosen** (Wali/PI/Pembimbing TA) — search by **NIM + Nama** (keduanya wajib, exact match — Bagian 5), AJAX/Fetch API, hasil instan tanpa reload
- 4 item Bagian 6 — tampil sebagai download PDF (final)

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
- [ ] **Modul pencarian dosen** — query WAJIB mensyaratkan **NIM + Nama cocok bersamaan** (Bagian 5), exact match, bukan `LIKE`/partial.
- [ ] 🆕 *(Rekomendasi tambahan, belum dikonfirmasi klien)* Rate limiting di endpoint pencarian dosen sebagai lapis pertahanan tambahan terhadap enumerasi otomatis.

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
| `student_advisors` | `id`, `nim` (index), `student_name`, `advisor_name`, `advisor_type` (Wali/Magang/TA) | TRUNCATE + reload tiap semester dari CSV baru — validasi penuh sebelum truncate. 🆕 Rekomendasi: composite index `(nim, student_name)` untuk performa pencarian NIM+Nama (Bagian 5) |

## 14. Approval Klien

> Persetujuan scope teknis (Bagian 5 & 6) sudah diterima informal via WhatsApp dari Pak Dimas, 6 Juli 2026 (lihat Bagian 15). Bagian di bawah untuk approval formal/tanda tangan, kalau diperlukan sebagai penutup resmi proyek atau lampiran administratif (mis. laporan Praktik Industri).

- [ ] Nama: ______________________
- [ ] Jabatan: ______________________
- [ ] Tanda tangan & tanggal: ______________________

## 15. Log Keputusan & Riwayat Perubahan

| Tanggal | Versi | Perubahan | Sumber Konfirmasi |
|---------|-------|-----------|--------------------|
| 6 Juli 2026 | 3.1 | Draft internal disusun; 1 open question (Q1, modul pencarian dosen) belum terjawab klien | — |
| 6 Juli 2026 | 4.0 | **Q1 dijawab:** modul pencarian dosen final = NIM + Nama Lengkap, ikut rekomendasi tim. **Bagian 6** (4 item PDF) turut disetujui otomatis lewat pernyataan klien yang sama. Ditambahkan **Bagian 5A** (mekanisme evaluasi mingguan, Jumat sore). Catatan lama soal referensi "Q3" diverifikasi sebagai sisa draft basi (bukan pertanyaan tersisa) — dihapus. | Pak Dimas Pamilih, via WhatsApp |

**Kutipan lengkap jawaban klien (6 Juli 2026, WhatsApp):**
> "Ok mas, dilanjutkan pengerjaannya sesuai dengan rekomendasi teman2 tim dulu, saya ngikut saja, nanti ketika evaluasi setiap hari jumat sore sebelum pulang kita lihat progressnya bersama, perlu ada tambahan/perbaikan."
