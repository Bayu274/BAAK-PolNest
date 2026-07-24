# LAPORAN TEKNIS PROYEK — Portal Informasi BAAK Politeknik Nest

> **Dokumen:** Laporan Status & Rencana Teknis Lengkap
> **Proyek:** Portal Informasi BAAK — Politeknik Nest, Sukoharjo
> **Dibuat:** 22 Juli 2026 | **Terakhir diperbarui:** 24 Juli 2026
> **Referensi:** PRD.md v4.0 (Final/Baseline), Pembagian_Tugas_Branch_GitHub.md
> **PIC Klien:** Pak Dimas Pamilih

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Arsitektur & Stack Teknis](#2-arsitektur--stack-teknis)
3. [Inventaris File Lengkap](#3-inventaris-file-lengkap)
4. [Status Pengerjaan per Fitur (vs PRD)](#4-status-pengerjaan-per-fitur-vs-prd)
5. [Modul yang Sudah Selesai — Dev 3 (Bug Fix Phase 1–4)](#5-modul-yang-sudah-selesai--dev-3-bug-fix-phase-14)
6. [Temuan Bug & Masalah Kode (Findings)](#6-temuan-bug--masalah-kode-findings)
7. [Gap Analysis: PRD vs Implementasi](#7-gap-analysis-prd-vs-implementasi)
8. [Rencana Kerja Sisa (Roadmap ke 100%)](#8-rencana-kerja-sisa-roadmap-ke-100)
9. [Panduan Deployment](#9-panduan-deployment)
10. [Appendix: Detail Teknis](#10-appendix-detail-teknis)

---

## 1. Ringkasan Eksekutif

### Status Proyek: ~75% Selesai

Portal BAAK adalah CMS/Portal Informasi Publik untuk BAAK Politeknik Nest. Sistem ini bukan SIAKAD — fokusnya adalah sentralisasi informasi, pencarian dosen pembimbing (NIM + Nama), dan manajemen konten oleh admin.

**Yang sudah selesai:**
- Core framework (Router, Controller, Database, Session) — oleh Dev 1
- autentikasi admin (login/logout, bcrypt, session regeneration) — oleh Dev 1
- CRUD Berita (create, read, update, delete + thumbnail upload) — oleh Dev 2
- CRUD Halaman (create, read, update, delete + CKEditor) — oleh Dev 2
- Modul Pencarian Dosen (AJAX exact match, rate limiting, CSRF, CSP) — oleh Dev 3
- Import CSV Data Pembimbing (validasi berlapis, atomic swap, backup) — oleh Dev 3
- Manajemen File Upload (PDF/DOCX, soft delete, FOR UPDATE lock) — oleh Dev 3
- Security hardening (CSRF regen, CSP nonce, rate limit rewrite, HTMLPurifier) — oleh Dev 3
- Frontend layout wrapper, accessibility, flash messages — oleh Dev 3

**Yang masih perlu dikerjakan:**
- Bug di `pages_content` (kolom `title` tidak ada di schema)
- `page-detail.php` menampilkan konten hardcoded, bukan dari database
- `emit_security_headers()` belum dipanggil di manapun
- `NewsController` dan `PageController` masih punya 17 `die()` calls
- Dashboard admin masih kosong (belum ada widget/data)
- README.md hampir kosong
- Rate limit cleanup mechanism belum diputuskan
- Frontend layout belum optimal (navbar, footer perlu diperkaya)

---

## 2. Arsitektur & Stack Teknis

### Stack
| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP Native 8.2 (tanpa framework) |
| Database | MariaDB 10.4.32 (MySQL compatible) |
| Frontend | Bootstrap 5.3.3 (CDN), Bootstrap Icons 1.11.3 |
| Rich Text | CKEditor 5 Classic (CDN) |
| AJAX | Fetch API (vanilla JS) |
| Security | HTMLPurifier 4.15.0 (bundled) |

### Arsitektur MVC Custom
```
index.php (Front Controller)
    ↓
Router.php (URL → Controller mapping, supports {param} wildcards)
    ↓
Controller.php (base class: render(), requireLogin(), jsonResponse())
    ↓
controllers/ → models/ → views/
```

### Database (5 tabel + 1 rate limit)
| Tabel | Fungsi | Record Count |
|-------|--------|-------------|
| `admin_users` | Akun admin (1 record: admin/admin) | 1 |
| `news` | Berita & pengumuman | 5 |
| `pages_content` | Konten halaman statis (SOP) | 1 |
| `downloadable_files` | File PDF/DOCX yang diunggah admin | 4 |
| `student_advisors` | Data NIM–Nama–Dosen Pembimbing | Import dari CSV |
| `rate_limit_attempts` | Rate limiting counters | Auto-cleanup |

### Routing (index.php)
| Method | Route | Handler | Akses |
|--------|-------|---------|-------|
| GET | `/` | HomeController::index() | Publik |
| GET | `/jadwal` | HomeController::showJadwal() | Publik |
| GET | `/pencarian-dosen` | AdvisorController::showSearchPage() | Publik |
| POST | `/api/advisors/search` | AdvisorController::search() | Publik (AJAX) |
| GET | `/berita/{slug}` | NewsController::show() | Publik |
| GET | `/halaman/{identifier}` | PageController::show() | Publik |
| GET | `/login` | AuthController::showLoginForm() | Publik |
| POST | `/login` | AuthController::login() | Publik |
| GET | `/logout` | AuthController::logout() | Admin |
| GET | `/dashboard` | DashboardController::index() | Admin |
| GET | `/admin/news` | NewsController::listAdmin() | Admin |
| GET | `/admin/news/create` | NewsController::createForm() | Admin |
| POST | `/admin/news/store` | NewsController::store() | Admin |
| GET | `/admin/news/edit?id=N` | NewsController::editForm() | Admin |
| POST | `/admin/news/update` | NewsController::update() | Admin |
| POST | `/admin/news/delete` | NewsController::delete() | Admin |
| GET | `/admin/pages` | PageController::listAdmin() | Admin |
| GET | `/admin/pages/create` | PageController::createForm() | Admin |
| POST | `/admin/pages/store` | PageController::store() | Admin |
| POST | `/admin/pages/delete` | PageController::delete() | Admin |
| GET | `/admin/pages/edit/{id}` | PageController::editForm() | Admin |
| POST | `/admin/pages/save/{id}` | PageController::save() | Admin |
| GET | `/admin/files` | FileController::listAdmin() | Admin |
| POST | `/admin/files/upload` | FileController::store() | Admin |
| POST | `/admin/files/delete` | FileController::delete() | Admin |
| GET | `/admin/import-csv` | AdvisorController::importCsvForm() | Admin |
| POST | `/admin/import-csv` | AdvisorController::processImport() | Admin |

---

## 3. Inventaris File Lengkap

### PHP Files (34)

| File | Baris | Fungsi | Status |
|------|-------|--------|--------|
| `index.php` | 100 | Front controller, route definitions | ✅ |
| `config/constants.php` | 13 | BASE_URL, BASE_PATH, APP_ENV | ✅ |
| `config/database.php` | 34 | PDO singleton, getDbConnection() | ✅ |
| `config/logger.php` | 46 | logError, logInfo, logWarning | ✅ |
| `config/security.php` | 198 | CSRF, rate limit, CSP nonce, e(), HTMLPurifier | ✅ |
| `core/Controller.php` | 67 | Base class: render(), requireLogin(), jsonResponse() | ✅ |
| `core/Router.php` | 53 | Custom router, {param} wildcards | ✅ |
| `controllers/AuthController.php` | 80 | Login, logout, rate limit, session regenerate | ✅ |
| `controllers/DashboardController.php` | 10 | Stub — render backend/layout kosong | ⚠️ Kosong |
| `controllers/AdvisorController.php` | 226 | Search AJAX + CSV import | ✅ |
| `controllers/FileController.php` | 159 | Upload/download file + cleanup | ✅ |
| `controllers/HomeController.php` | 21 | index() + showJadwal() | ✅ |
| `controllers/NewsController.php` | 234 | CRUD berita + thumbnail upload | ⚠️ Masih ada die() |
| `controllers/PageController.php` | 154 | CRUD halaman + CKEditor | ⚠️ Masih ada die() |
| `models/Admin.php` | 13 | findByUsername() | ✅ |
| `models/Advisor.php` | 130 | findByNimAndName(), truncateAndReload(), backup | ✅ |
| `models/DownloadableFile.php` | 89 | getActiveFiles(), replaceByCategory(), deactivate() | ✅ |
| `models/News.php` | 64 | CRUD berita + getBySlug() | ✅ |
| `models/Page.php` | 58 | CRUD halaman, create() pakai kolom `title` | ❌ Bug schema |
| `views/frontend/layout.php` | 34 | Frontend layout (navbar + footer) | ✅ |
| `views/frontend/home.php` | ~60 | Landing page + feed berita | ✅ |
| `views/frontend/search-dosen.php` | 65 | Form pencarian AJAX + CSP nonce | ✅ |
| `views/frontend/jadwal.php` | 45 | Halaman jadwal & pedoman | ✅ |
| `views/frontend/news-detail.php` | ~30 | Detail berita (rich HTML output) | ✅ |
| `views/frontend/page-detail.php` | ~40 | Detail halaman — HARDCODED, bukan dari DB | ❌ Bug |
| `views/frontend/login.php` | ~40 | Form login (standalone, punya DOCTYPE sendiri) | ⚠️ |
| `views/backend/layout.php` | 42 | Admin sidebar + loading indicator JS | ✅ |
| `views/backend/news-list.php` | ~60 | Tabel list berita | ✅ |
| `views/backend/news-form.php` | ~80 | Form create/edit berita + CKEditor | ✅ |
| `views/backend/pages-list.php` | ~60 | Tabel list halaman | ✅ |
| `views/backend/pages-create.php` | ~50 | Form tambah halaman + CKEditor | ✅ |
| `views/backend/pages-edit.php` | ~70 | Form edit halaman + CKEditor | ✅ |
| `views/backend/files-manage.php` | 108 | Upload file + tabel file aktif | ✅ |
| `views/backend/advisor-import.php` | 64 | Form upload CSV | ✅ |

### JavaScript (1)
| File | Baris | Fungsi | Status |
|------|-------|--------|--------|
| `assets/js/search-dosen.js` | 128 | AJAX search, AbortController, loading state | ✅ |

### SQL (2)
| File | Fungsi | Status |
|------|--------|--------|
| `schema_polinest_baak.sql` | Schema lengkap (CREATE TABLE + indexes + constraints) | ⚠️ Missing `title` di `pages_content` |
| `migrations/001_lower_existing_data.sql` | Lowercase existing student_advisors data | ✅ |

### Dokumentasi (6)
| File | Isi | Status |
|------|-----|--------|
| `README.md` | 2 baris saja | ❌ Perlu diperkaya |
| `PRD.md` | PRD lengkap v4.0, 177 baris | ✅ Final |
| `PRD_Technical_Design_Modul_Pencarian_Dosen.md` | Technical design search module | ✅ |
| `Pembagian_Tugas_Branch_GitHub.md` | Pembagian tugas 3 dev + branch strategy | ✅ |
| `todo.md` | File ini (laporan teknis) | ✅ |
| `test.md` | Test log Phase 1–4, semua PASS | ✅ |

### Lainnya
| File | Fungsi |
|------|--------|
| `.htaccess` | URL rewriting → index.php |
| `.gitignore` | Ignore uploads, .env, logs |
| `storage/uploads/.htaccess` | Deny from all (security) |
| `storage/uploads/doc_*.pdf` | 4 file PDF yang diunggah |
| `storage/backups/` | CSV backup sebelum import |
| `dummy.csv` | Contoh CSV untuk testing |
| `Alur Sistem (Flow Chart Teknis).txt` | Flow chart 2 alur utama |
| `struktur-folder.txt` | Struktur folder (agak outdated) |

---

## 4. Status Pengerjaan per Fitur (vs PRD)

### PRD Requirement Coverage Matrix

| No | Kebutuhan BAAK | PRD § | Status | Keterangan |
|----|---------------|-------|--------|------------|
| 1 | Kalender Akademik | §6 | ✅ Selesai | PDF unduhan, dikelola admin via FileController |
| 2 | Daftar MK per Prodi | §6 | ✅ Selesai | PDF unduhan, kategori `jadwal_kuliah` |
| 3 | Daftar Dosen Wali Kelas | §5 | ✅ Selesai | Pencarian NIM+Nama, AJAX exact match |
| 4 | Koordinator MK | §6 | ✅ Selesai | PDF unduhan, dikelola admin |
| 5 | Daftar Dosen PI (Magang) | §5 | ✅ Selesai | Pencarian NIM+Nama, tipe `Magang` |
| 6 | Daftar Dosen Pembimbing TA | §5 | ✅ Selesai | Pencarian NIM+Nama, tipe `TA` |
| 7 | Daftar Kuliah, UTS, UAS | §6 | ✅ Selesai | PDF unduhan, dikelola admin |
| 8 | Jadwal Seminar | §6 | ✅ Selesai | PDF unduhan, kategori `jadwal_kuliah` |
| 9 | Form Rencana Studi (KRS) | §6 | ✅ Selesai | Template unduhan, bukan transaksional |
| 10 | Form Cuti | §6 | ✅ Selesai | Template unduhan |
| 11 | Form Mengundurkan Diri | §6 | ✅ Selesai | Template unduhan |
| 12 | Situs RPS (link) | §6 | ⚠️ Partial | Link ada di jadwal.php tapi arah ke admin/files |
| 13 | Buku Pedoman Akademik | §6 | ✅ Selesai | PDF unduhan, kategori `panduan_ta` |
| 14 | Jadwal Pelayanan BAAK | §6 | ✅ Selesai | PDF unduhan + info kontak di jadwal.php |
| 15 | Form Pindah Kelas | §6 | ✅ Selesai | Template unduhan |

### Backend Features

| Fitur | PRD § | Status | Keterangan |
|-------|-------|--------|------------|
| Login Admin (bcrypt) | §10B | ✅ Selesai | password_hash + session_regenerate_id |
| CRUD Berita | §10B | ⚠️ 90% | die() calls masih ada, perlu diganti flash messages |
| Manajemen Konten Halaman | §10B | ⚠️ 80% | die() calls, `page-detail.php` hardcoded |
| Manajemen File (PDF/DOCX) | §10B | ✅ Selesai | Upload, soft delete, FOR UPDATE lock |
| Import CSV Data Pembimbing | §10B | ✅ Selesai | Validasi berlapis, atomic swap, backup |
| Dashboard Admin | §10B | ❌ 0% | Masih kosong, belum ada widget/data |

### Security

| Requirement | PRD § | Status | Keterangan |
|-------------|-------|--------|------------|
| SQL Injection Prevention | §11 | ✅ | Semua query pakai Prepared Statements |
| XSS Prevention | §11 | ⚠️ | news-detail.php output raw HTML (intentional untuk CKEditor content, relies on HTMLPurifier at save) |
| Backend Access Control | §11 | ✅ | requireLogin() di semua admin controller |
| CSRF Tokens | §11 | ✅ | Token + regen setelah submit |
| Password Hashing | §11 | ✅ | password_hash() + password_verify() |
| Exact Match Search | §5 | ✅ | WHERE nim = ? AND student_name = ? |
| Rate Limiting | §5,§11 | ✅ | IP-based + endpoint-based, fail-closed |

---

## 5. Modul yang Sudah Selesai — Dev 3 (Bug Fix Phase 1–4)

Dev 3 mengerjakan 4 phase perbaikan yang sudah terdokumentasi lengkap di `test.md`:

### Phase 1 — Foundation & Security (10 fixes)
- [x] 1A: CSRF token regeneration setelah form submit
- [x] 1B: CSP nonce-based untuk inline scripts
- [x] 1H: Schema — rate_limit_attempts UNIQUE KEY, student_advisors UNIQUE KEY
- [x] 1I: Schema — news index untuk performa
- [x] 1G: Controller.php — multi-layout support (bool|string)
- [x] 1F: HTMLPurifier fallback jika library tidak ada
- [x] 1D: HSTS conditional (hanya HTTPS)
- [x] 1E: Rate limit fail-closed + atomic upsert

### Phase 2 — Core Logic & Data (18 fixes)
- [x] 2A: Hapus LOWER() dari SQL query Advisor
- [x] 2B: Normalize via mb_strtolower() di insert
- [x] 2C: Atomic swap via TEMPORARY TABLE
- [x] 2D: Explicit columns di SELECT (bukan SELECT *)
- [x] 2E: FOR UPDATE lock di replaceByCategory
- [x] 2F: getActiveFileNames() untuk orphan cleanup
- [x] 2G: JSON `is_array()` guard di search endpoint
- [x] 2H–2M: die() → importError() flash (10 replacements)
- [x] 2I: fgetcsv unlimited length
- [x] 2J: BOM strip + header casing normalization
- [x] 2K: CSV deduplication per NIM+type
- [x] 2L: Max-row limit 50.000
- [x] 2M: CSRF regeneration setelah import
- [x] 2N: catch Throwable (bukan hanya Exception)
- [x] 2O: Hapus admin ID fallback `?? 1`
- [x] 2P: Audit logging (logInfo/logError)
- [x] 2Q: cleanupOrphanedFiles() method
- [x] 2R–2T: JS innerHTML save/restore, AbortController timeout, empty array check

### Phase 3 — Views & Frontend (15 fixes)
- [x] 3A: views/frontend/layout.php (FILE BARU)
- [x] 3B–3E: search-dosen.php — nonce, BASE_URL escaping, label for, aria-live
- [x] 3F: controllers use 'frontend' layout
- [x] 3G: Backend Bootstrap Icons CSS
- [x] 3H–3J: files-manage.php — flash messages, label for, strtotime
- [x] 3K: CSRF consistency (htmlspecialchars → e())
- [x] 3M–3N: HomeController frontend layout + route /jadwal
- [x] 3O: Dead links di jadwal.php

### Phase 4 — Polish & Extras (4 fixes)
- [x] 4A: FileController die() → fileError() flash (8 replacements)
- [x] 4B: Loading indicators di semua form buttons
- [x] 4C: SQL migration untuk lowercase existing data
- [x] 4D: Backup mechanism sebelum CSV truncate

**Status:** Semua 47 fixes PASS. Test log di `test.md`. Zero breaking changes.

---

## 6. Temuan Bug & Masalah Kode (Findings)

### CRITICAL (Harus diperbaiki sebelum deploy)

#### BUG-01: `pages_content` Schema vs Code Mismatch
- **File:** `schema_polinest_baak.sql` baris 83–89 vs `models/Page.php` baris 41
- **Masalah:** `Page::create()` mengeksekusi `INSERT INTO pages_content (page_identifier, title, html_content, updated_by)` — tapi tabel `pages_content` di schema **TIDAK punya kolom `title`**. Hanya punya: `id`, `page_identifier`, `html_content`, `updated_by`, `last_updated`.
- **Dampak:** Admin tidak bisa membuat halaman baru — INSERT akan gagal dengan MySQL error "Unknown column 'title'".
- **Fix:** Tambah kolom `title` ke schema `pages_content`, atau hapus `title` dari query INSERT di Page model.
- **Pilihan:** Menambah `title` lebih baik karena `pages-list.php` menampilkan `$p['title']`.

#### BUG-02: `page-detail.php` Hardcoded Content
- **File:** `views/frontend/page-detail.php` baris 25–39
- **Masalah:** Konten halaman SOP ditulis hardcoded di view, **bukan dari database**. `$page['html_content']` dari model TIDAK digunakan.
- **Dampak:** Admin mengedit konten di backend → perubahan TIDAK terlihat di halaman publik. Semua halaman SOP statis.
- **Fix:** Render `$page['html_content']` alih-alih konten hardcoded. Perlu sanitasi output (HTMLPurifier sudah jalan di save).

#### BUG-03: `emit_security_headers()` Belum Dipanggil
- **File:** `config/security.php` baris 25–55
- **Masalah:** Fungsi `emit_security_headers()` sudah ditulis lengkap (CSP, HSTS, X-Frame-Options, dll.) tapi **tidak dipanggil di manapun** — termasuk tidak di `index.php`.
- **Dampak:** Tidak ada security headers yang dikirim ke browser. CSP nonce tidak aktif. X-Frame-Options tidak aktif.
- **Fix:** Panggil `emit_security_headers()` di `index.php` sebelum output HTML/JSON.

### HIGH (Perlu diperbaiki sebelum demo ke klien)

#### BUG-04: Dashboard Admin Kosong
- **File:** `controllers/DashboardController.php`
- **Masalah:** Method `index()` hanya render `backend/layout` dengan `$content = null`. Tidak ada widget, tidak ada statistik, tidak ada data.
- **Dampak:** Admin login → lihat halaman kosong dengan sidebar saja. Tidak profesional.
- **Fix:** Tambah statistik (jumlah berita, jumlah file, jumlah data dosen, login terakhir) atau minimal welcome message.

#### BUG-05: 17 die() Calls di NewsController & PageController
- **File:** `controllers/NewsController.php` (9 calls), `controllers/PageController.php` (8 calls)
- **Masalah:** Kedua controller masih menggunakan `die()` untuk error handling. Ini konsisten dengan pola yang sudah diperbaiki di FileController dan AdvisorController (Phase 2–4), tapi belum di-touch.
- **Dampak:** User melihat error mentah ("Error 403: CSRF Violation") tanpa context. Tidak ada logging. Tidak ada redirect yang graceful.
- **Fix:** Buat helper `newsError()` dan `pageError()` (mirip `fileError()` dan `importError()`), ganti semua die().

#### BUG-06: `login.php` Tidak Pakai Frontend Layout
- **File:** `views/frontend/login.php`
- **Masalah:** Login page punya DOCTYPE sendiri, tidak menggunakan `views/frontend/layout.php`. Navbar dan footer tidak muncul di halaman login.
- **Dampak:** Login page terlihat terpisah dari situs. Tidak konsisten.
- **Fix:** Render login via Controller::render() dengan layout frontend, atau buat login layout sendiri.

### MEDIUM (Perlu diperbaiki untuk kualitas kode)

#### BUG-07: `news-detail.php` Raw HTML Output
- **File:** `views/frontend/news-detail.php` baris 21
- **Masalah:** `<?= $news['content'] ?>` — output tanpa htmlspecialchars. Ini **intentional** untuk konten CKEditor (rich text), tapi bergantung sepenuhnya pada HTMLPurifier saat save. Jika HTMLPurifier gagal atau path-nya salah, XSS vulnerability terbuka.
- **Dampak:** Jika admin bisa meng-upload file HTMLPurifier tidak ada, konten berisi script jahat bisa ter-render.
- **Fix:** Pertahankan raw output (karena memang rich text), tapi tambah fallback: cek apakah HTMLPurifier loaded, log warning jika tidak.

#### BUG-08: Rate Limit Cleanup Belum Diimplementasi
- **File:** `config/security.php` — checkRateLimit()
- **Masalah:** Record lama di `rate_limit_attempts` di-cleanup inline (DELETE sebelum SELECT), tapi tidak ada mekanisme cleanup global untuk semua IP/endpoint. Tabel akan terus membesar seiring waktu.
- **Dampak:** Lambat laun tabel rate_limit_attempts membesar tanpa batas. Query DELETE+SELECT makin lambat.
- **Fix:** (1) Jalankan cleanup global periodic (cron atau MySQL Event Scheduler), atau (2) tambah garbage collection di index.php.

#### BUG-09: `pages_content` Schema Tidak Konsisten dengan CRUD
- **File:** `schema_polinest_baak.sql` vs `models/Page.php` vs `views/backend/pages-*.php`
- **Masalah:** Schema tidak punya `title`, tapi `pages-list.php` menampilkan `$p['title']`, `pages-create.php` punya input `title`, `Page::create()` INSERT `title`.
- **Dampak:** Sama dengan BUG-01. Semua operasi create/update terkait `title` akan gagal.
- **Fix:** Tambah kolom `title VARCHAR(255) NOT NULL DEFAULT ''` ke schema.

### LOW (Nice to have, bisa dikerjakan nanti)

#### BUG-10: README.md Hampir Kosong
- **File:** `README.md`
- **Masalah:** Hanya 2 baris: "# BAAK-PolNest" + deskripsi 1 baris. Tidak ada setup instruction, tech stack, cara jalankan, environment variables, dll.
- **Dampak:** Developer baru tidak tahu cara setup project ini.
- **Fix:** Tulis README lengkap: prerequisites, installation, configuration, usage, folder structure.

#### BUG-11: `struktur-folder.txt` Outdated
- **File:** `struktur-folder.txt`
- **Masalah:** Tidak mencantumkan DashboardController, FileController, PageController, HomeController, DownloadableFile model, backend views (advisor-import, files-manage), frontend layout, jadwal, dll.
- **Dampak:** Dokumen tidak akurat, bisa menyesatkan.
- **Fix:** Update atau hapus file ini.

#### BUG-12: Frontend Navbar Kurang Lengkap
- **File:** `views/frontend/layout.php` baris 18–22
- **Masalah:** Navbar hanya punya "Beranda" dan "Cari Dosen". Link ke "Jadwal" dan "Berita" belum ada.
- **Dampak:** User harus tau URL manual untuk akses jadwal dan berita.
- **Fix:** Tambah menu "Jadwal" dan "Berita" ke navbar.

#### BUG-13: Frontend Footer Minimalis
- **File:** `views/frontend/layout.php` baris 28–30
- **Masalah:** Footer hanya menampilkan copyright. Tidak ada link ke halaman lain, tidak ada info kontak.
- **Dampak:** Footer tidak fungsional.
- **Fix:** Tambah link ke Beranda, Jadwal, Pencarian Dosen, dan info kontak BAAK.

---

## 7. Gap Analysis: PRD vs Implementasi

### Fitur yang Belum Implementasi

| PRD Reference | Kebutuhan | Status | Gap |
|---------------|-----------|--------|-----|
| §10B | Dashboard admin dengan overview data | ❌ Belum | DashboardController kosong |
| §10B | Upload/overwrite PDF dengan histori | ✅ Selesai | — |
| §10B | Rich text editor untuk konten halaman | ✅ Selesai | CKEditor 5 integrated |
| §10B | Import CSV dengan validasi penuh | ✅ Selesai | — |
| §12 | Responsive/mobile-friendly | ✅ Selesai | Bootstrap 5 |
| §12 | AJAX/Fetch API untuk pencarian | ✅ Selesai | — |

### Fitur yang Implementasi Tapi Ada Bug

| Fitur | Bug | Severity |
|-------|-----|----------|
| Halaman publik SOP | Hardcoded content, bukan dari DB | CRITICAL |
| CRUD halaman | Kolom `title` tidak ada di schema | CRITICAL |
| Security headers | Belum dipanggil | CRITICAL |
| Login page | Tidak pakai frontend layout | HIGH |
| Dashboard admin | Kosong | HIGH |

### Open Decisions (dari Pembagian_Tugas_Branch_GitHub.md §5)

| No | Keputusan | Status | Keterangan |
|----|-----------|--------|------------|
| 1 | Multi-advisor response format | ✅ Sudah ditangani | findByNimAndName() return array, JS render per baris |
| 2 | Rich text HTML sanitization | ✅ Sudah ditangani | HTMLPurifier 4.15.0 bundled |
| 3 | Rate limit cleanup mechanism | ❌ Belum diputuskan | Inline DELETE+SELECT sudah jalan, tapi global cleanup belum |

---

## 8. Rencana Kerja Sisa (Roadmap ke 100%)

### Phase 5 — Critical Bug Fixes (Estimasi: 2–3 jam)

| No | Task | File | Assign | Prioritas |
|----|------|------|--------|-----------|
| 5A | Tambah kolom `title` ke `pages_content` schema | `schema_polinest_baak.sql` | Dev 3 | CRITICAL |
| 5B | Fix `page-detail.php` — render dari DB, bukan hardcoded | `views/frontend/page-detail.php` | Dev 2/3 | CRITICAL |
| 5C | Panggil `emit_security_headers()` di `index.php` | `index.php` | Dev 1/3 | CRITICAL |
| 5D | Buat migration untuk tambah kolom `title` ke DB existing | `migrations/002_add_pages_title.sql` | Dev 3 | CRITICAL |

### Phase 6 — High Priority Improvements (Estimasi: 3–4 jam)

| No | Task | File | Assign | Prioritas |
|----|------|------|--------|-----------|
| 6A | Ganti die() → flash messages di NewsController (9 calls) | `controllers/NewsController.php` | Dev 3 | HIGH |
| 6B | Ganti die() → flash messages di PageController (8 calls) | `controllers/PageController.php` | Dev 3 | HIGH |
| 6C | Buat Dashboard admin (statistik sederhana) | `controllers/DashboardController.php`, `views/backend/dashboard.php` | Dev 1/2 | HIGH |
| 6D | Integrasi login.php ke frontend layout | `views/frontend/login.php`, `controllers/AuthController.php` | Dev 1 | HIGH |

### Phase 7 — Medium Priority Polish (Estimasi: 2–3 jam)

| No | Task | File | Assign | Prioritas |
|----|------|------|--------|-----------|
| 7A | Rate limit cleanup global (MySQL Event Scheduler atau cron) | `schema_polinest_baak.sql`, `config/security.php` | Dev 3 | MEDIUM |
| 7B | Tambah menu ke frontend navbar (Jadwal, Berita) | `views/frontend/layout.php` | Dev 2 | MEDIUM |
| 7C | Perkaya frontend footer (link + kontak) | `views/frontend/layout.php` | Dev 2 | MEDIUM |
| 7D | Tambah 404 error page | `views/errors/404.php`, `core/Router.php` | Dev 1 | MEDIUM |
| 7E | Tambah error log untuk HTMLPurifier fallback | `config/security.php` | Dev 3 | MEDIUM |

### Phase 8 — Low Priority & Documentation (Estimasi: 2–3 jam)

| No | Task | File | Assign | Prioritas |
|----|------|------|--------|-----------|
| 8A | Tulis README.md lengkap | `README.md` | Dev 1 | LOW |
| 8B | Update struktur-folder.txt | `struktur-folder.txt` | Dev 1 | LOW |
| 8C | Environment config (.env.example) | `.env.example` | Dev 1 | LOW |
| 8D | Backup mechanism: schedule auto-cleanup old backups | `models/Advisor.php` | Dev 3 | LOW |

### Timeline Estimasi

| Phase | Jam | Dependencies | Target |
|-------|-----|-------------|--------|
| Phase 5 — Critical | 2–3 jam | Tidak ada | Sebelum demo |
| Phase 6 — High | 3–4 jam | Phase 5 | Minggu ini |
| Phase 7 — Medium | 2–3 jam | Phase 5+6 | Minggu depan |
| Phase 8 — Low | 2–3 jam | Phase 5+6 | Sebelum closing |
| **Total** | **9–13 jam** | — | — |

### Total Estimasi Proyek

| Komponen | Jam |
|----------|-----|
| Dev 1 — Core & Auth | ~8 jam (sudah selesai) |
| Dev 2 — Content Delivery | ~10 jam (sudah selesai) |
| Dev 3 — Search & Security | ~17 jam (sudah selesai, termasuk Phase 1–4 bug fix) |
| Sisa kerja (Phase 5–8) | 9–13 jam |
| **Grand Total** | **44–48 jam** |

---

## 9. Panduan Deployment

### Prerequisites
- PHP 8.2+ dengan extensi: PDO, pdo_mysql, mbstring, fileinfo, json, session
- MariaDB 10.4+ atau MySQL 5.7+
- Web server (Apache dengan mod_rewrite, atau Nginx + PHP-FPM)
- Directory `storage/uploads/` dan `storage/backups/` writable oleh web server

### Langkah Deployment

1. **Clone repository ke web server**
   ```bash
   git clone <repo-url> /var/www/baak-polnest
   ```

2. **Import database schema**
   ```bash
   mysql -u root -p polinest_baak < schema_polinest_baak.sql
   ```

3. **Jalankan migration (jika ada data existing)**
   ```bash
   mysql -u root -p polinest_baak < migrations/001_lower_existing_data.sql
   mysql -u root -p polinest_baak < migrations/002_add_pages_title.sql  # setelah Phase 5
   ```

4. **Setup directory permissions**
   ```bash
   chmod -R 755 storage/
   chown -R www-data:www-data storage/
   ```

5. **Konfigurasi database credentials**
   Edit `config/database.php` — sesuaikan host, dbname, username, password.

6. **Konfigurasi BASE_URL**
   Otomatis terdeteksi dari `$_SERVER['HTTP_HOST']` dan `$_SERVER['SCRIPT_NAME']`.
   Untuk subdirectory, pastikan `.htaccess` dan `BASE_PATH` benar.

7. **Pastikan `.htaccess` aktif**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
   ```

8. **Test login**
   Buka `/login` → login dengan admin/admin → ganti password segera!

### Environment Variables (Rekomendasi untuk Produksi)
Buat file `.env` atau set di `config/database.php`:
```
APP_ENV=production
DB_HOST=127.0.0.1
DB_NAME=polinest_baak
DB_USER=root
DB_PASS=<password>
```

---

## 10. Appendix: Detail Teknis

### A. Security Layer (config/security.php)

| Fungsi | Status | Keterangan |
|--------|--------|------------|
| `generateCsrfToken()` | ✅ | Token 64-char hex, timing-safe |
| `regenerateCsrfToken()` | ✅ | Regen setelah form submit |
| `verifyCsrfToken()` | ✅ | hash_equals() — timing-safe |
| `generateCspNonce()` | ✅ | Nonce 32-byte hex per session |
| `emit_security_headers()` | ⚠️ Ditulis tapi belum dipanggil | CSP, HSTS, X-Frame-Options |
| `checkRateLimit()` | ✅ | Atomic upsert, fail-closed |
| `e()` | ✅ | htmlspecialchars() wrapper |
| `sanitizeHtmlContent()` | ✅ | HTMLPurifier dengan fallback |

### B. Database Schema Indexes

| Tabel | Index | Fungsi |
|-------|-------|--------|
| `admin_users` | UNIQUE(username) | Cegah duplikat admin |
| `news` | UNIQUE(slug) | URL SEO-friendly |
| `news` | INDEX(created_at) | Query berita terbaru |
| `pages_content` | UNIQUE(page_identifier) | URL halaman |
| `downloadable_files` | INDEX(file_category, is_active) | Query file per kategori |
| `student_advisors` | UNIQUE(nim, advisor_type) | Cegah duplikat data |
| `student_advisors` | INDEX(nim, student_name) | Query pencarian |
| `rate_limit_attempts` | UNIQUE(ip_address, endpoint, window_start) | Atomic upsert |
| `rate_limit_attempts` | INDEX(window_start) | Cleanup query |

### C. API Response Format

**Search Endpoint** — `POST /api/advisors/search`
```json
// Sukses:
{
    "status": "success",
    "data": [
        {
            "advisor_name": "dr. budi santoso, m.kom",
            "advisor_type": "Wali"
        },
        {
            "advisor_name": "ir. ahmad, m.sc",
            "advisor_type": "Magang"
        }
    ]
}

// Error (semua case — generic message):
{
    "status": "error",
    "message": "Data tidak ditemukan atau kecocokan tidak valid."
}

// Rate limit:
{
    "status": "error",
    "message": "Terlalu banyak percobaan. Silakan coba lagi nanti."
}
```

### D. File Upload Flow

```
User → Form submit → FileController::store()
  1. Verify CSRF token
  2. Validate category (whitelist)
  3. Validate upload error
  4. Validate size (≤ 10MB)
  5. Validate extension (pdf/docx)
  6. Validate MIME type (finfo)
  7. Generate random filename (doc_[hex].ext)
  8. Move uploaded file
  9. Database: soft-deactivate old + insert new (FOR UPDATE lock)
  10. Regenerate CSRF token
  11. Log audit trail
  12. Redirect with flash message
```

### E. CSV Import Flow

```
User → Form submit → AdvisorController::processImport()
  1. Verify CSRF token
  2. Validate upload error
  3. Validate size (≤ 5MB)
  4. Validate extension (.csv)
  5. Validate MIME type (finfo)
  6. Read CSV header → strip BOM → normalize casing → validate columns
  7. Parse rows → validate column count, advisor_type values
  8. Max-row limit (50,000)
  9. Deduplication per NIM+type
  10. Backup current data → CSV file (keep last 5)
  11. Atomic swap via TEMPORARY TABLE:
      a. CREATE TEMPORARY TABLE LIKE student_advisors
      b. INSERT all rows (with normalize)
      c. DROP TABLE student_advisors
      d. RENAME tmp_student_advisors TO student_advisors
  12. Regenerate CSRF token
  13. Redirect with status=success
```

---

> **Dokumen ini adalah acuan tunggal untuk seluruh tim development.**
> Setiap perubahan scope wajib dicatat di dokumen ini (update versi + log keputusan), sesuai §5A PRD.md.
