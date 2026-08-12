# LAPORAN TEKNIS PROYEK — Portal Informasi BAAK Politeknik Nest

> **Dokumen:** Laporan Status & Rencana Teknis Lengkap
> **Proyek:** Portal Informasi BAAK — Politeknik Nest, Sukoharjo
> **Dibuat:** 22 Juli 2026 | **Terakhir diperbarui:** 05 Agustus 2026
> **Referensi:** PRD.md v4.0 (Final/Baseline), Pembagian_Tugas_Branch_GitHub.md
> **PIC Klien:** Pak Dimas Pamilih
> **Status:** ✅ Fitur 100% + Security Audit 100% + Post-Audit Fixes SELESAI — Phase 9A–9D (50 task) + Phase 10 (7 task) SEMUA DIPERBAIKI + Phase 11–13 (Katalog Berita, Layanan BAAK, Template CSV & Panduan XAMPP) + Phase 14 (Design System Frontend) + Phase 15 (Fix Login Admin) + Phase 16 (Legacy MD5 + Auto-Upgrade) + Phase 17 (Sinkronisasi Update 3 Agustus & Sisa Tugas: RPS Link, Sidebar Responsive, Kolom Akun Admin, Draft Berita) SELESAI

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

### Status Proyek

Portal BAAK adalah CMS/Portal Informasi Publik untuk BAAK Politeknik Nest. Sistem ini bukan SIAKAD — fokusnya adalah sentralisasi informasi, pencarian dosen pembimbing (NIM + Nama), dan manajemen konten oleh admin.

**Fitur fungsional: 100% SELESAI.**
Semua modul sudah bekerja sesuai PRD. Phase 1–8 (64 fixes) sudah PASS syntax check.

**Keamanan & Kualitas Kode: AUDIT DITEMUKAN 50+ TEMUAN BARU.**
Audit menyeluruh ke seluruh codebase (34 file PHP, 12 backend views, 6 frontend views) menemukan isu keamanan dan kualitas kode yang sebelumnya tidak terdeteksi. Temuan ini perlu diperbaiki sebelum layak deploy ke produksi.

**Perbaikan sebelumnya (Phase 1–9, sudah selesai):**
- Phase 1–4 (47 fixes) — oleh Dev 3: CSRF regen, CSP nonce, rate limit, atomic swap, HTMLPurifier, loading spinner, migration
- Phase 5–8 (17 task) — oleh AI Assistant: schema title, page-detail dari DB, security headers dipanggil, die()→flash, dashboard admin, navbar/footer, .gitignore
- **Phase 9 (50 task) — oleh AI Assistant: Security Audit Fixes**
  - 9A: 5 CRITICAL — CSP whitelist CKEditor, nonce backend views, XSS page-detail/news-detail, DB credentials ke env vars
  - 9B: 12 HIGH — Host Header validation, logout POST-only, log injection fix, .htaccess protection, backend layout viewport+JS, session username
  - 9C: 18 MEDIUM — SQL prepared statement, orphaned files cleanup, escape views, log permissions+cleanup, session flash messages, .env.example, .gitignore
  - 9D: 15 LOW — SELECT*→explicit columns, extension whitelist, EXTR_SKIP, login length validation, login Bootstrap JS removal
- **Phase 10 (7 task) — oleh AI Assistant: Post-Audit Bug Fixes**
  - Standarisasi `e()` di 3 view files (7 instance `htmlspecialchars()` → `e()`)
  - Fix column name mismatch `updated_at` → `last_updated` di pages-list.php (BUG)
  - Fix `logError()` dipanggil sebelum logger.php di-load di database.php (BUG)
  - Buat `storage/backups/.htaccess` (Deny from all)
  - Tambah warning admin password hash di README.md

**Yang perlu diperbaiki (Phase 9 — Security Audit, 50+ temuan):**
~~Semua sudah diperbaiki. Lihat §8.2 untuk detail.~~

**Post-Audit Bug Fixes (Phase 10, sudah selesai):**
- Kolom name mismatch di pages-list.php → fix ke `last_updated`
- logError() dipanggil sebelum logger loaded → ganti ke error_log()
- storage/backups/.htaccess belum ada → dibuat
- Admin password hash di schema → warning ditambah di README

**Update 3 Agustus 2026 (teman tim — disinkronkan 5 Agustus):**
- Bayu San (`c1d4eb4`): auto-setup DB/tabel/admin (`config/setup.php` + `ensureAppReady()`), normalisasi tautan rich text (`sanitizeHtmlContent` cache + `normalizeRichContentLinks`), atomic swap CSV via staging table reguler + recovery, `fgetcsv` null, layout frontend di 404, dummy CSV.
- KaYeYe (`afe8831`): hapus PDF upload test dari tracking (sudah di .gitignore), tambah dump debug `current_*.txt`.

**Phase 17 (5 Agustus 2026, sudah selesai):** Sisa tugas dituntaskan — PRD No.12 Situs RPS (link keluar via `RPS_URL`), AUDIT-L11 sidebar backend responsive (offcanvas mobile), AUDIT-L12 kolom akun admin (`email`/`is_active`/`last_login_at` + cek login + auto-upgrade skema), AUDIT-L13 draft berita (`news.is_active` + filter publik + UI). Detail di §8.9.

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
| GET | `/berita` | NewsController::indexPublic() | Publik |
| GET | `/berita/{slug}` | NewsController::show() | Publik |
| GET | `/layanan` | PageController::indexPublic() | Publik |
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
| GET | `/admin/import-csv/template` | AdvisorController::downloadTemplate() | Admin |
| GET | `/admin/import-csv` | AdvisorController::importCsvForm() | Admin |
| POST | `/admin/import-csv` | AdvisorController::processImport() | Admin |

---

## 3. Inventaris File Lengkap

### PHP Files (35)

| File | Baris | Fungsi | Status |
|------|-------|--------|--------|
| `index.php` | 101 | Front controller, route definitions | ✅ |
| `config/constants.php` | 13 | BASE_URL, BASE_PATH, APP_ENV | ✅ Host validation ditambahkan |
| `config/database.php` | 34 | PDO singleton, getDbConnection() | ✅ Env vars + error_log fix |
| `config/logger.php` | 46 | logError, logInfo, logWarning | ✅ Log injection fix |
| `config/security.php` | 266 | CSRF, rate limit, CSP nonce, e(), HTMLPurifier, sanitizeHtmlContent + cache, normalizeRichContentLinks | ✅ CSP + prepared stmts fix + link normalization |
| `config/setup.php` | 203 | Auto-setup DB/tabel/admin + auto-upgrade kolom (ensureAppSchemaColumns) | ✅ Baru (3 Agu) + upgrade kolom |
| `core/Controller.php` | 67 | Base class: render(), requireLogin(), jsonResponse() | ✅ |
| `core/Router.php` | 53 | Custom router, {param} wildcards | ✅ |
| `controllers/AuthController.php` | 64 | Login, logout, rate limit, session regenerate | ✅ POST-only logout + session username fix |
| `controllers/DashboardController.php` | 30 | Dashboard admin dengan statistik | ✅ |
| `controllers/AdvisorController.php` | 226 | Search AJAX + CSV import | ✅ |
| `controllers/FileController.php` | 159 | Upload/download file + cleanup | ✅ |
| `controllers/HomeController.php` | 25 | index() + showJadwal() | ✅ |
| `controllers/NewsController.php` | 260 | CRUD berita + thumbnail upload | ✅ |
| `controllers/PageController.php` | 160 | CRUD halaman + CKEditor | ✅ |
| `models/Admin.php` | 13 | findByUsername() | ✅ |
| `models/Advisor.php` | 130 | findByNimAndName(), truncateAndReload(), backup | ✅ |
| `models/DownloadableFile.php` | 89 | getActiveFiles(), replaceByCategory(), deactivate() | ✅ |
| `models/News.php` | 64 | CRUD berita + getBySlug() | ✅ |
| `models/Page.php` | 58 | CRUD halaman, create() pakai kolom `title` | ✅ |
| `views/frontend/layout.php` | 63 | Frontend layout (navbar + footer, 3 kolom) | ✅ |
| `views/frontend/home.php` | 51 | Landing page + feed berita | ✅ |
| `views/frontend/search-dosen.php` | 65 | Form pencarian AJAX + CSP nonce | ✅ |
| `views/frontend/jadwal.php` | 45 | Halaman jadwal & pedoman | ✅ |
| `views/frontend/news-detail.php` | 38 | Detail berita (rich HTML output) | ✅ Sanitasi + e() fix |
| `views/frontend/page-detail.php` | 32 | Detail halaman (dari DB) | ✅ Sanitasi output |
| `views/frontend/login.php` | 45 | Form login (standalone DOCTYPE) | ✅ |
| `views/backend/layout.php` | 46 | Admin sidebar + loading indicator JS | ✅ Viewport + Bootstrap JS + nonce |
| `views/backend/dashboard.php` | 134 | Dashboard admin statistik | ✅ |
| `views/backend/news-list.php` | 64 | Tabel list berita | ✅ |
| `views/backend/news-form.php` | 76 | Form create/edit berita + CKEditor | ✅ Nonce ditambahkan |
| `views/backend/pages-list.php` | — | Tabel list halaman | ✅ Column fix + e() fix |
| `views/backend/pages-create.php` | 48 | Form tambah halaman + CKEditor | ✅ CSRF nullable + nonce + e() fix |
| `views/backend/pages-edit.php` | 65 | Form edit halaman + CKEditor | ✅ Nonce ditambahkan |
| `views/backend/files-manage.php` | 108 | Upload file + tabel file aktif | ✅ |
| `views/backend/advisor-import.php` | 64 | Form upload CSV | ✅ |

### JavaScript (1)
| File | Baris | Fungsi | Status |
|------|-------|--------|--------|
| `assets/js/search-dosen.js` | 128 | AJAX search, AbortController, loading state | ✅ |

### SQL (5)
| File | Fungsi | Status |
|------|-------|--------|
| `schema_polinest_baak.sql` | Schema lengkap (CREATE TABLE + indexes + constraints) | ✅ + kolom admin_users & news |
| `migrations/001_lower_existing_data.sql` | Lowercase existing student_advisors data | ✅ |
| `migrations/002_add_pages_title.sql` | Tambah kolom `title` ke pages_content (idempotent) | ✅ |
| `migrations/003_admin_users_extra.sql` | Tambah kolom `email`, `is_active`, `last_login_at` ke admin_users (idempotent) | ✅ |
| `migrations/004_news_is_active.sql` | Tambah kolom `is_active` ke news (draft/publikasi, idempotent) | ✅ |

### Dokumentasi (6)
| File | Isi | Status |
|------|-----|--------|
| `README.md` | Dokumentasi lengkap setup & penggunaan | ✅ |
| `PRD.md` | PRD lengkap v4.0, 177 baris | ✅ Final |
| `PRD_Technical_Design_Modul_Pencarian_Dosen.md` | Technical design search module | ✅ |
| `Pembagian_Tugas_Branch_GitHub.md` | Pembagian tugas 3 dev + branch strategy | ✅ |
| `todo.md` | File ini (laporan teknis) | ✅ |
| `test.md` | Test log Phase 1–4, semua PASS | ✅ |

### Lainnya
| File | Fungsi |
|------|--------|
| `.htaccess` | URL rewriting + Options -Indexes + block path terlarang |
| `.gitignore` | Ignore uploads, .env, logs, IDE files |
| `.env.example` | Template environment configuration |
| `config/.htaccess` | Deny from all — proteksi credentials |
| `storage/uploads/.htaccess` | Disable PHP exec + ForceType + gambar diizinkan |
| `storage/uploads/doc_*.pdf` | 4 file PDF yang diunggah |
| `storage/backups/.htaccess` | Deny from all — proteksi CSV backup |
| `storage/backups/` | CSV backup sebelum import |
| `storage/logs/.htaccess` | Deny from all — proteksi log files |
| `storage/logs/` | Log file harian (.log) |
| `config/` | Database credentials (via env vars), security config |
| `dummy.csv` | Contoh CSV untuk testing |
| `dummy_dosen_pembimbing.csv` | Dummy data dosen pembimbing (22 baris, 3 Agu) |
| `current_constants.txt`, `current_controller.txt`, `current_security.txt` | Dump debug KaYeYe (3 Agu) |
| `Alur Sistem (Flow Chart Teknis).txt` | Flow chart 2 alur utama |
| `struktur-folder.txt` | Struktur folder |

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
| 12 | Situs RPS (link) | §6 | ✅ Selesai | Kartu link keluar di jadwal.php → `RPS_URL` (konstanta + env var) |
| 13 | Buku Pedoman Akademik | §6 | ✅ Selesai | PDF unduhan, kategori `panduan_ta` |
| 14 | Jadwal Pelayanan BAAK | §6 | ✅ Selesai | PDF unduhan + info kontak di jadwal.php |
| 15 | Form Pindah Kelas | §6 | ✅ Selesai | Template unduhan |

### Backend Features

| Fitur | PRD § | Status | Keterangan |
|-------|-------|--------|------------|
| Login Admin (bcrypt) | §10B | ✅ Selesai | password_hash + session_regenerate_id |
| CRUD Berita | §10B | ✅ Selesai | die() sudah diganti flash messages (Phase 6) |
| Manajemen Konten Halaman | §10B | ✅ Selesai | die() sudah diganti, page-detail render dari DB |
| Manajemen File (PDF/DOCX) | §10B | ✅ Selesai | Upload, soft delete, FOR UPDATE lock |
| Import CSV Data Pembimbing | §10B | ✅ Selesai | Validasi berlapis, atomic swap, backup |
| Dashboard Admin | §10B | ✅ Selesai | Statistik berita, file, dosen, halaman |

### Security (Pre-Audit)

| Requirement | PRD § | Status | Keterangan |
|-------------|-------|--------|------------|
| SQL Injection Prevention | §11 | ✅ | Semua query pakai Prepared Statements |
| XSS Prevention | §11 | ✅ | HTMLPurifier di save + sanitize saat render + link normalization (3 Agu) |
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

> **UPDATE 24 Juli 2026:** Temuan di bawah ini berasal dari **audit menyeluruh** ke seluruh codebase (34 PHP, 12 backend views, 6 frontend views) oleh AI Assistant. Ditemukan **50+ isu baru** yang belum tercatat di Phase 1–8.
>
> Temuan sebelumnya (BUG-01 s/d BUG-13) sudah diperbaiki di Phase 5–8. Lihat §8.2 untuk catatan perbaikan sebelumnya.

### 6.1 CRITICAL — Harus Diperbaiki Sekarang (5 temuan)

#### AUDIT-C1: CSP Memblokir CKEditor — Admin Panel Rusak
- **File:** `config/security.php` baris 42
- **Masalah:** Header CSP `script-src` hanya mengizinkan `'self'`, `'nonce-...'`, dan `https://cdn.jsdelivr.net`. CKEditor 5 dimuat dari `https://cdn.ckeditor.com` yang **tidak ada di whitelist**. Browser akan memblokir CKEditor sepenuhnya.
- **Dampak:** Seluruh admin panel tidak bisa membuat/mengedit berita dan halaman karena CKEditor tidak bisa dimuat.
- **Fix:** Tambahkan `https://cdn.ckeditor.com` ke whitelist `script-src` di CSP header.

#### AUDIT-C2: CSP Memblokir Semua Inline Script Backend
- **File:** `views/backend/layout.php:34`, `views/backend/news-form.php:68`, `views/backend/pages-create.php:42`, `views/backend/pages-edit.php:57`
- **Masalah:** Semua `<script>` inline di backend views **tidak memiliki atribut `nonce`**. CSP mengharuskan inline script punya nonce yang valid. Browser memblokir semua script ini.
- **Dampak:** Form submit spinner mati, CKEditor tidak bisa diinisialisasi, semua interaktivitas backend hilang.
- **Fix:** Tambahkan `nonce="<?= generateCspNonce() ?>"` ke semua tag `<script>` inline di backend views, atau gunakan `style-src 'unsafe-inline'` approach untuk script.

#### AUDIT-C3: Stored XSS di `page-detail.php`
- **File:** `views/frontend/page-detail.php:18`
- **Masalah:** `<?= $page['html_content'] ?>` — output raw HTML tanpa sanitasi output. Meskipun HTMLPurifier sudah jalan saat save di controller, jika HTMLPurifier tidak ter-load (path salah, library corrupt), konten berisi `<script>` akan ter-render mentah.
- **Dampak:** Stored XSS — admin bisa menyisipkan script jahat yang dieksekusi semua pengunjung halaman.
- **Fix:** Panggil `sanitizeHtmlContent()` sebagai defense-in-depth saat render, atau minimal tambah fallback check seperti di `news-detail.php`.

#### AUDIT-C4: Stored XSS di `news-detail.php` — Conditional Pecah
- **File:** `views/frontend/news-detail.php:25-30`
- **Masalah:** Conditional `if (function_exists('sanitizeHtmlContent'))` ada, tapi **kedua branch melakukan hal yang sama** — `echo $news['content']` tanpa memanggil `sanitizeHtmlContent()`. Conditional ini tidak berguna.
- **Dampak:** Stored XSS — konten berita yang disimpan tanpa sanitasi bisa mengeksekusi script.
- **Fix:** Branch yang valid harus memanggil `echo sanitizeHtmlContent($news['content'])`. Branch fallback harus `echo htmlspecialchars($news['content'])`.

#### AUDIT-C5: Database Credentials Hardcoded
- **File:** `config/database.php:8-11`
- **Masalah:** Username `root` dengan password kosong di-hardcode di source code. Tidak ada env-based configuration.
- **Dampak:** Jika repository bocor, attacker mendapat akses database langsung. Di production server, root tanpa password = risiko tinggi.
- **Fix:** Baca dari environment variables (`getenv('DB_HOST')`), sediakan `.env.example` sebagai template.

### 6.2 HIGH — Perlu Diperbaiki Sebelum Deploy (12 temuan)

#### AUDIT-H1: Host Header Injection
- **File:** `config/constants.php:6,11`
- **Masalah:** `$_SERVER['HTTP_HOST']` dipakai langsung untuk `BASE_URL` tanpa validasi. Attacker bisa set header `Host: evil.com` dan mendapat base URL yang berbahaya.
- **Dampak:** Cache poisoning, password reset poisoning (jika ada fitur reset), header injection.
- **Fix:** Validasi `$_SERVER['HTTP_HOST']` against whitelist host atau regex `^[a-zA-Z0-9.-]+$`.

#### AUDIT-H2: Logout via GET (CSRF Logout)
- **File:** `index.php:37`
- **Masalah:** `/logout` bisa dipanggil via GET request. Attacker bisa paksa admin logout dengan `<img src=".../logout">` atau link sederhana.
- **Dampak:** Session fixation / forced logout — admin tidak bisa bekerja.
- **Fix:** Ganti logout ke POST-only dengan CSRF token verification.

#### AUDIT-H3: Log Injection
- **File:** `config/logger.php:12-24`
- **Masalah:** `$message`, `$ip`, `$uri` ditulis ke log file tanpa sanitasi. Attacker bisa mengirim request dengan karakter newline untuk forge log entries palsu.
- **Dampak:** Log manipulation, forensik tidak akurat, potential log analysis bypass.
- **Fix:** Strip newlines dari semua input: `str_replace(["\r", "\n"], '', $message)`.

#### AUDIT-H4: Dev-Mode `die()` Leak PDO Error
- **File:** `config/database.php:28`
- **Masalah:** `die('Koneksi database gagal: ' . $e->getMessage())` — jika `APP_ENV` tidak diset, error detail (host, username, SQL state) ditampilkan ke user.
- **Dampak:** Informasi bocor: DB host, username, versi driver, sebagian SQL query.
- **Fix:** Selalu tampilkan pesan generik, log error detail. Hapus branch `die()` dev-mode.

#### AUDIT-H5: Tidak Ada `Options -Indexes` di Root `.htaccess`
- **File:** `.htaccess`
- **Masalah:** Root `.htaccess` hanya berisi RewriteRule. Tidak ada `Options -Indexes` atau `RewriteRule` untuk block akses ke folder terlarang.
- **Dampak:** Directory listing aktif — semua file dan folder project terexpose via browser.
- **Fix:** Tambahkan `Options -Indexes` dan rewrite rules untuk block akses ke `config/`, `storage/logs/`, `storage/backups/`, `migrations/`, `models/`, `core/`.

#### AUDIT-H6: Tidak Ada `.htaccess` di `config/`
- **File:** `config/` (folder tanpa `.htaccess`)
- **Masalah:** File `database.php` (credentials), `security.php`, `constants.php` bisa diakses langsung via HTTP.
- **Dampak:** Database credentials dan security configuration terekspos ke publik.
- **Fix:** Buat `config/.htaccess` dengan `Deny from all`.

#### AUDIT-H7: Tidak Ada `.htaccess` di `storage/logs/`
- **File:** `storage/logs/` (folder tanpa `.htaccess`)
- **Masalah:** Log file berisi IP addresses, URIs, error messages bisa diakses publik.
- **Dampak:** Informasi sensitif bocor (IP internal, stack traces, user agents).
- **Fix:** Buat `storage/logs/.htaccess` dengan `Deny from all`.

#### AUDIT-H8: Backend Layout — Viewport & Bootstrap JS Hilang
- **File:** `views/backend/layout.php:4-6`
- **Masalah:** Tidak ada `<meta name="viewport">` tag, tidak ada `bootstrap.bundle.min.js`. Admin panel tidak responsive di mobile. `data-bs-dismiss="alert"` pada flash messages tidak fungsi.
- **Dampak:** Admin panel tidak bisa diakses dari mobile. Alert dismiss tidak bekerja.
- **Fix:** Tambahkan viewport meta tag dan Bootstrap JS CDN ke backend layout.

#### AUDIT-H9: `$_SESSION['admin_username']` Tidak Pernah Di-Set
- **File:** `controllers/AuthController.php:47` vs `views/backend/dashboard.php:6`
- **Masalah:** Login hanya set `$_SESSION['admin_id']`, tapi dashboard menampilkan `$_SESSION['admin_username'] ?? 'Admin'`. Variable `admin_username` tidak pernah di-set.
- **Dampak:** Dashboard selalu tampilkan "Admin" generik, bukan nama username aktual.
- **Fix:** Set `$_SESSION['admin_username'] = $admin['username']` saat login berhasil.

#### AUDIT-H10: `$_GET['id']` Tidak Di-Cast Integer
- **File:** `index.php:54`
- **Masalah:** `$id = $_GET['id'] ?? null` — value dari query string tidak di-cast ke integer sebelum diteruskan ke controller.
- **Dampak:** Jika attacker mengirim `?id[]=1`, array bisa masuk ke controller, menyebabkan unexpected behavior.
- **Fix:** `$id = isset($_GET['id']) ? (int)$_GET['id'] : null`.

#### AUDIT-H11: `$_POST['id']` Tidak Di-Cast Integer
- **File:** `index.php:62`
- **Masalah:** `$id = $_POST['id'] ?? null` — sama seperti H10 tapi untuk POST data di news delete.
- **Dampak:** Unexpected behavior jika attacker manipulasi POST data.
- **Fix:** `$id = isset($_POST['id']) ? (int)$_POST['id'] : null`.

#### AUDIT-H12: Router 404 Response Plain Text
- **File:** `core/Router.php:36-37`
- **Masalah:** 404 response hanya `echo "404 - Halaman tidak ditemukan"` — plain text tanpa HTML layout.
- **Dampak:** User melihat halaman kosong/plain text, tidak profesional. Tidak ada branding.
- **Fix:** Render halaman 404 dengan HTML layout (minimal header + footer).

### 6.3 MEDIUM — Perlu Diperbaiki untuk Kualitas (18 temuan)

#### AUDIT-M1: SQL String Concatenation di Rate Limit Cleanup
- **File:** `config/security.php:184`
- **Masalah:** `$db->exec("DELETE FROM rate_limit_attempts WHERE window_start < '{$cutoff}'")` — string concatenation dalam SQL, bukan prepared statement.
- **Dampak:** Meskipun `$cutoff` berasal dari `date()` (aman), ini melanggar pola konsisten prepared statements dan bisa menjadi kebiasaan buruk.
- **Fix:** Gunakan prepared statement: `$db->prepare("DELETE FROM rate_limit_attempts WHERE window_start < ?")`.

#### AUDIT-M2: Orphaned Files Tidak Pernah Di-Cleanup
- **File:** `controllers/FileController.php:141-158`
- **Masalah:** Method `cleanupOrphanedFiles()` sudah ditulis tapi **tidak pernah dipanggil** dari mana pun.
- **Dampak:** File fisik yang sudah di-soft-delete dari DB tetap ada di disk, memakan storage.
- **Fix:** Panggil `cleanupOrphanedFiles()` secara periodic (cron) atau saat file baru di-upload.

#### AUDIT-M3: Unescaped `$item['id']` di HTML
- **File:** `views/backend/news-list.php:43`
- **Masalah:** `value="<?= $item['id'] ?>"` tanpa escaping. Seharusnya `value="<?= (int)$item['id'] ?>"`.
- **Dampak:** Minor XSS potential jika data di database terkompromi.
- **Fix:** Cast ke `(int)` atau gunakan `e()`.

#### AUDIT-M4: Unescaped `$stats` di Dashboard
- **File:** `views/backend/dashboard.php:18,35,52,69`
- **Masalah:** `<?= $stats['news'] ?>` dan sejenisnya tanpa `(int)` cast. Meskipun sudah di-cast di controller, defense-in-depth menyarankan escaping di view.
- **Dampak:** Minor XSS potential.
- **Fix:** `<?= (int)$stats['news'] ?>`.

#### AUDIT-M5: `htmlspecialchars(null)` Deprecation Warning
- **File:** `views/backend/pages-create.php:12`
- **Masalah:** `value="<?= htmlspecialchars($csrf_token) ?>"` — jika `$csrf_token` null, PHP 8.2 menghasilkan deprecation warning.
- **Dampak:** PHP deprecation warning di error log.
- **Fix:** `value="<?= htmlspecialchars($csrf_token ?? '') ?>"`.

#### AUDIT-M6: Inconsistent `e()` vs `htmlspecialchars()`
- **File:** Banyak file
- **Masalah:** Beberapa file pakai `e()` (helper), yang lain `htmlspecialchars()` langsung. Tidak konsisten.
- **Dampak:** Maintenance burden. Tidak ada security risk, tapi codebase kurang rapi.
- **Fix:** Standarisasi ke `e()` di semua view.

#### AUDIT-M7: Missing Connection Timeout di PDO
- **File:** `config/database.php:15-19`
- **Masalah:** Tidak ada `PDO::ATTR_TIMEOUT` option. Jika DB down, PHP akan hanging sampai default timeout (60 detik).
- **Dampak:** Slow loris / DoS — attacker bisa trigger banyak request yang hanging.
- **Fix:** Tambahkan `PDO::ATTR_TIMEOUT => 5` (5 detik).

#### AUDIT-M8: Log File Permissions `0755`
- **File:** `config/logger.php:16`
- **Masalah:** `mkdir($logDir, 0755)` — log directory bisa di-read oleh semua user di shared hosting.
- **Dampak:** Log file berisi informasi sensitif bisa dibaca user lain.
- **Fix:** Gunakan `0750` untuk directory dan `0640` untuk file.

#### AUDIT-M9: Tidak Ada Log Rotation / Cleanup
- **File:** `config/logger.php`
- **Masalah:** Log file harian (`YYYY-MM-DD.log`) menumpuk tanpa batas. Tidak ada mekanisme hapus log lama.
- **Dampak:** Storage server penuh seiring waktu.
- **Fix:** Tambahkan cleanup log lebih dari 30 hari, atau gunakan Monolog dengan RotatingFileHandler.

#### AUDIT-M10: GET-Based Flash Messages di `pages-edit.php`
- **File:** `views/backend/pages-edit.php:10`
- **Masalah:** `$_GET['status'] == 'success'` untuk menampilkan flash message, sementara controller lain pakai `$_SESSION`.
- **Dampak:** Inconsistent pattern. GET parameter bisa dimanipulasi (show success message palsu).
- **Fix:** Gunakan session-based flash messages seperti controller lain.

#### AUDIT-M11: `storage/uploads/.htaccess` — ForceType Memblokir Gambar
- **File:** `storage/uploads/.htaccess:17`
- **Masalah:** `ForceType application/octet-stream` diterapkan ke semua file. `FilesMatch` hanya membuka PDF/DOCX. File gambar (thumbnail) ter-force download.
- **Dampak:** Thumbnail images tidak bisa di-preview di browser.
- **Fix:** Tambahkan `image/jpeg`, `image/png` ke FilesMatch, atau gunakan approach berbeda.

#### AUDIT-M12: Migration 002 Tidak Idempotent
- **File:** `migrations/002_add_pages_title.sql`
- **Masalah:** `ALTER TABLE ... ADD COLUMN` tanpa check apakah column sudah ada. Jika dijalankan 2x, error "Duplicate column name".
- **Dampak:** Migration gagal saat dijalankan ulang.
- **Fix:** Tambahkan check: `ALTER TABLE ... ADD COLUMN IF NOT EXISTS ...` (MariaDB 10.0.2+) atau wrap dalam procedure.

#### AUDIT-M13: Missing `.env.example`
- **File:** Project root
- **Masalah:** Tidak ada `.env.example` sebagai template untuk environment configuration.
- **Dampak:** Developer baru tidak tahu environment variables apa yang tersedia.
- **Fix:** Buat `.env.example` dengan semua variabel yang didukung.

#### AUDIT-M14: `.gitignore` Kurang Lengkap
- **File:** `.gitignore`
- **Masalah:** Tidak ada entry untuk IDE files (`*.iml`, `.idea/`, `.vscode/`), `vendor/`, `*.log`, `composer.lock`.
- **Dampak:** File-file ini bisa ter-commit ke repository.
- **Fix:** Tambahkan entry yang diperlukan.

#### AUDIT-M15: News Slug Bisa Duplikat
- **File:** `controllers/NewsController.php:219-221`
- **Masalah:** `generateSlug()` tidak mengecek uniqueness. Jika dua berita punya judul sama, slug akan sama — melanggar UNIQUE constraint di DB.
- **Dampak:** INSERT gagal dengan DB error, user melihat error mentah.
- **Fix:** Tambahkan suffix numerik (`-2`, `-3`) sampai slug unik.

#### AUDIT-M16: `PageController::editForm()` Tidak Cek Null
- **File:** `controllers/PageController.php:120-131`
- **Masalah:** `editForm($identifier)` tidak mengecek apakah `$page` null sebelum render view. Jika identifier tidak ditemukan, view mendapat `$page = false`.
- **Dampak:** PHP warning/notice di view saat akses `$page['html_content']`.
- **Fix:** Tambahkan null check sebelum render, redirect ke halaman error jika tidak ditemukan.

#### AUDIT-M17: Silent Redirect on 404 (News & Page Controllers)
- **File:** `controllers/NewsController.php:39`, `controllers/PageController.php:30`
- **Masalah:** Jika berita/halaman tidak ditemukan, user di-redirect ke home tanpa pesan error apapun.
- **Dampak:** User tidak tahu kenapa halaman tidak muncul. Broken link tidak terdeteksi.
- **Fix:** Render 404 page dengan pesan yang jelas, atau set `http_response_code(404)`.

#### AUDIT-M18: `$_GET` Flash Messages di `pages-edit.php` Bisa Dimanipulasi
- **File:** `views/backend/pages-edit.php:10`
- **Masalah:** `$_GET['status'] == 'success'` — attacker bisa kirim `?status=success` untuk menampilkan pesan sukses palsu.
- **Dampak:** Social engineering — user percaya operasi berhasil padahal belum.
- **Fix:** Gunakan session-based flash messages.

### 6.4 LOW — Nice to Have (15 temuan)

#### AUDIT-L1: Dead Code — `Controller::jsonResponse()`
- **File:** `core/Controller.php:60-66`
- **Masalah:** Method `jsonResponse()` tidak pernah dipanggil dari controller manapun.
- **Dampak:** Dead code, maintenance burden.
- **Fix:** Pertahankan jika future use, atau hapus.

#### AUDIT-L2: Dead Code — `FileController::cleanupOrphanedFiles()`
- **File:** `controllers/FileController.php:141-158`
- **Masalah:** Method private, tidak pernah dipanggil (sama dengan AUDIT-M2).
- **Dampak:** Dead code.
- **Fix:** Panggil method ini, atau hapus.

#### AUDIT-L3: `SELECT *` di Model News dan Page
- **File:** `models/News.php:9,29,46,60`, `models/Page.php:12`
- **Masalah:** `SELECT * FROM news`, `SELECT * FROM pages_content` — mengambil semua kolom tanpa spesifikasi.
- **Dampak:** Performance overhead jika kolom bertambah. Bisa leak kolom sensitif di masa depan.
- **Fix:** Spesifikasikan kolom yang dibutuhkan.

#### AUDIT-L4: Thumbnail Upload — Tidak Ada Extension Whitelist di Controller
- **File:** `controllers/NewsController.php:224-258`
- **Masalah:** Validasi pakai MIME type (finfo), tapi extension dari filename tidak dicek. File `.php` dengan MIME image/jpeg lolos finfo (file bisa dicraft).
- **Dampak:** Potensi file upload bypass jika attacker craft file.
- **Fix:** Tambahkan whitelist extension: `['jpg', 'jpeg', 'png']`.

#### AUDIT-L5: Session Cookie Tidak Di-Clear Saat Logout
- **File:** `controllers/AuthController.php:60`
- **Masalah:** `session_destroy()` tanpa `setcookie()` untuk clear session cookie.
- **Dampak:** Session cookie tetap ada di browser, bisa dipakai untuk session fixation.
- **Fix:** Tambahkan `setcookie(session_name(), '', time() - 3600, '/')` sebelum `session_destroy()`.

#### AUDIT-L6: `extract()` Bisa Overwrite Variabel Lokal
- **File:** `core/Controller.php:12`
- **Masalah:** `extract($data)` dalam method `render()` bisa overwrite variabel `$view`, `$viewPath`, `$content`, `$layoutPath` jika data mengandung key yang sama.
- **Dampak:** Bug hard-to-trace jika view data punya key yang konflik.
- **Fix:** Gunakan `EXTR_SKIP` flag atau rename data keys.

#### AUDIT-L7: Tidak Ada Input Length Validation di Login
- **File:** `controllers/AuthController.php:26-27`
- **Masalah:** `trim($_POST['username'])` tanpa panjang maksimum. Attacker bisa kirim string 1MB.
- **Dampak:** Memory exhaustion (minor).
- **Fix:** `mb_substr($username, 0, 50)` atau validasi max length.

#### AUDIT-L8: Full URI Logged (Termasuk Query String)
- **File:** `config/logger.php:22`
- **Masalah:** `$_SERVER['REQUEST_URI']` termasuk query string, bisa berisi CSRF token, passwords, dll.
- **Dampak:** Sensitive data logged dalam plain text.
- **Fix:** Log hanya path: `parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)`.

#### AUDIT-L9: Bootstrap JS Tidak Perlu di Login Page
- **File:** `views/frontend/login.php:43`
- **Masalah:** `bootstrap.bundle.min.js` di-load tapi tidak ada komponen Bootstrap JS yang dipakai di login page.
- **Dampak:** Unnecessary download overhead.
- **Fix:** Hapus script tag Bootstrap JS dari login page.

#### AUDIT-L10: Inconsistent Route Definitions (Closure vs Array)
- **File:** `index.php:44-63` vs `index.php:70-81`
- **Masalah:** News routes pakai closure `function() use (...)`, tapi Page/File/Advisor routes pakai array `[$controller, 'method']`.
- **Dampak:** Codebase kurang konsisten.
- **Fix:** Standarisasi ke salah satu pattern.

#### AUDIT-L11: Backend Sidebar Fixed 250px, Tidak Responsive
- **File:** `views/backend/layout.php:11`
- **Masalah:** `style="width: 250px; min-height: 100vh;"` — sidebar fixed width, di mobile sidebar menutupi konten.
- **Dampak:** Admin panel tidak usable di mobile (small screen).
- **Fix:** Buat sidebar collapsible di mobile, atau gunakan Bootstrap offcanvas.

#### AUDIT-L12: Missing `admin_users.email`, `last_login_at`, `is_active` Columns
- **File:** `schema_polinest_baak.sql`
- **Masalah:** Tabel `admin_users` hanya punya `id`, `username`, `password`. Tidak ada kolom email, last login, atau status aktif.
- **Dampak:** Fitur admin terbatas (tidak bisa track login terakhir, tidak bisa nonaktifkan akun).
- **Fix:** Tambah kolom via migration jika dibutuhkan.

#### AUDIT-L13: `news` Table Missing `is_active` Column
- **File:** `schema_polinest_baak.sql`
- **Masalah:** Tabel `news` tidak punya kolom `is_active` untuk soft delete/draft mode.
- **Dampak:** Tidak bisa menonaktifkan berita tanpa menghapusnya.
- **Fix:** Tambah kolom via migration jika dibutuhkan.

#### AUDIT-L14: Schema Dump Punya Admin Password Hash
- **File:** `schema_polinest_baak.sql:42`
- **Masalah:** Dump SQL berisi hash password admin. Jika ini password default, attacker bisa login.
- **Dampak:** Password default `admin` ter-expose.
- **Fix:** Ganti hash di schema dump, atau catat di README bahwa password harus diganti.

#### AUDIT-L15: `logWarning()` Mungkin Tidak Ter-Defined
- **File:** `views/frontend/news-detail.php:28`
- **Masalah:** `logWarning()` dipanggil tapi didefinisikan di `config/logger.php`. Jika file belum di-load, fatal error.
- **Dampak:** Fatal error jika load order berubah.
- **Fix:** Pastikan `config/logger.php` selalu di-load sebelum view, atau gunakan `function_exists()` check.

---

## 7. Gap Analysis: PRD vs Implementasi

### Fitur yang Sudah Implementasi

| PRD Reference | Kebutuhan | Status |
|---------------|-----------|--------|
| §10B | Dashboard admin dengan overview data | ✅ Selesai |
| §10B | Upload/overwrite PDF dengan histori | ✅ Selesai |
| §10B | Rich text editor untuk konten halaman | ✅ Selesai |
| §10B | Import CSV dengan validasi penuh | ✅ Selesai |
| §12 | Responsive/mobile-friendly | ⚠️ Frontend OK, backend sidebar belum responsive |
| §12 | AJAX/Fetch API untuk pencarian | ✅ Selesai |

### Fitur yang Implementasi Tapi Ada Bug

| Fitur | Bug | Severity | Status |
|-------|-----|----------|--------|
| CKEditor di admin | Diblokir oleh CSP | CRITICAL | ✅ Fixed Phase 9A-1 |
| Semua inline script backend | Diblokir oleh CSP tanpa nonce | CRITICAL | ✅ Fixed Phase 9A-2 |
| Halaman detail publik | Stored XSS — raw output tanpa sanitasi | CRITICAL | ✅ Fixed Phase 9A-3/4 + 3 Agu |
| Database credentials | Hardcoded root tanpa password | CRITICAL | ✅ Fixed Phase 9A-5 |
| Logout | Bisa di-trigger via GET (CSRF) | HIGH | ✅ Fixed Phase 9B-2 |
| Admin panel | Tidak responsive di mobile | HIGH | ✅ Fixed Phase 17-3 (offcanvas) |

### Open Decisions (dari Pembagian_Tugas_Branch_GitHub.md §5)

| No | Keputusan | Status | Keterangan |
|----|-----------|--------|------------|
| 1 | Multi-advisor response format | ✅ Sudah ditangani | findByNimAndName() return array |
| 2 | Rich text HTML sanitization | ✅ Sudah ditangani | HTMLPurifier di save + sanitize saat render (`sanitizeHtmlContent`) + `normalizeRichContentLinks` (3 Agu) |
| 3 | Rate limit cleanup mechanism | ✅ Sudah ditangani | Inline + global cleanup |

---

## 8. Rencana Kerja Sisa (Roadmap ke 100%)

### 8.1 Phase 5–8 (Sebelumnya — SEMUA SELESAI)

> Semua task di bawah ini sudah dikerjakan dan PASS syntax check (35 file PHP, 12/12 backend views, 6/6 frontend views).

#### Phase 5 — Critical Bug Fixes ✅ SELESAI

| No | Task | File | Status |
|----|------|------|--------|
| 5A | Tambah kolom `title` ke `pages_content` schema | `schema_polinest_baak.sql` | ✅ |
| 5B | Fix `page-detail.php` — render dari DB, bukan hardcoded | `views/frontend/page-detail.php` | ✅ |
| 5C | Panggil `emit_security_headers()` di `index.php` | `index.php` | ✅ |
| 5D | Buat migration untuk tambah kolom `title` ke DB existing | `migrations/002_add_pages_title.sql` | ✅ |

#### Phase 6 — High Priority Improvements ✅ SELESAI

| No | Task | File | Status |
|----|------|------|--------|
| 6A | Ganti die() → flash messages di NewsController (9 calls) | `controllers/NewsController.php` | ✅ |
| 6B | Ganti die() → flash messages di PageController (7 calls) | `controllers/PageController.php` | ✅ |
| 6C | Buat Dashboard admin (statistik sederhana) | `controllers/DashboardController.php`, `views/backend/dashboard.php` | ✅ |
| 6D | Perbaiki login.php (viewport, branding) | `views/frontend/login.php` | ✅ |

#### Phase 7 — Medium Priority Polish ✅ SELESAI

| No | Task | File | Status |
|----|------|------|--------|
| 7A | Rate limit cleanup global (session-throttled) | `config/security.php` | ✅ |
| 7B | Tambah menu ke frontend navbar (Jadwal) | `views/frontend/layout.php` | ✅ |
| 7C | Perkaya frontend footer (link + kontak) | `views/frontend/layout.php` | ✅ |
| 7D | Fix tag `<img>` rusak di home.php | `views/frontend/home.php` | ✅ |
| 7E | Fix tag `<img>` + breadcrumb di news-detail.php | `views/frontend/news-detail.php` | ✅ |
| 7F | Fix jadwal.php link ke admin area → info kontak | `views/frontend/jadwal.php` | ✅ |
| 7G | Tambah btn-submit ke semua form | views/backend/*.php | ✅ |

#### Phase 8 — Low Priority & Documentation ✅ SELESAI

| No | Task | File | Status |
|----|------|------|--------|
| 8A | Tulis README.md lengkap | `README.md` | ✅ |
| 8B | Update struktur-folder.txt | `struktur-folder.txt` | ✅ |
| 8C | Update .gitignore (tambah storage/backups/) | `.gitignore` | ✅ |
| 8D | Hapus dead code NewsController::index() | `controllers/NewsController.php` | ✅ |

---

### 8.2 Phase 9 — Security Audit Fixes (50+ temuan) ✅ SELESAI> **UPDATE 24 Juli 2026:** Phase 9A ✅ + 9B ✅ + 9C ✅ + 9D ✅ — **SELURUH SECURITY AUDIT SELESAI.** Total 50 task diperbaiki.

#### Phase 9A — CRITICAL Fixes (5 task) ✅ SELESAI

| No | Task | File | Temuan | Status |
|----|------|------|--------|--------|
| 9A-1 | Tambah `https://cdn.ckeditor.com` ke CSP `script-src` | `config/security.php:42` | AUDIT-C1 | ✅ |
| 9A-2 | Tambah nonce ke semua inline `<script>` backend views | `views/backend/layout.php`, `news-form.php`, `pages-create.php`, `pages-edit.php` | AUDIT-C2 | ✅ |
| 9A-3 | Tambah sanitasi output defense-in-depth di `page-detail.php` | `views/frontend/page-detail.php:18` | AUDIT-C3 | ✅ |
| 9A-4 | Fix conditional pecah di `news-detail.php` — panggil sanitizeHtmlContent() | `views/frontend/news-detail.php:25-30` | AUDIT-C4 | ✅ |
| 9A-5 | Pindah DB credentials ke environment variables | `config/database.php:8-11` | AUDIT-C5 | ✅ |

#### Phase 9B — HIGH Fixes (12 task) ✅ SELESAI

| No | Task | File | Temuan | Status |
|----|------|------|--------|--------|
| 9B-1 | Validasi `$_SERVER['HTTP_HOST']` | `config/constants.php:6` | AUDIT-H1 | ✅ |
| 9B-2 | Ganti logout ke POST-only + CSRF | `index.php:37`, `AuthController.php`, `views/backend/layout.php:20` | AUDIT-H2 | ✅ |
| 9B-3 | Strip newlines dari log input | `config/logger.php:12-24` | AUDIT-H3 | ✅ |
| 9B-4 | Hapus dev-mode `die()` — selalu tampilkan pesan generik | `config/database.php:25-29` | AUDIT-H4 | ✅ |
| 9B-5 | Tambah `Options -Indexes` + block path ke root `.htaccess` | `.htaccess` | AUDIT-H5 | ✅ |
| 9B-6 | Buat `config/.htaccess` — Deny from all | `config/.htaccess` (baru) | AUDIT-H6 | ✅ |
| 9B-7 | Buat `storage/logs/.htaccess` — Deny from all | `storage/logs/.htaccess` (baru) | AUDIT-H7 | ✅ |
| 9B-8 | Tambah viewport meta + Bootstrap JS ke backend layout | `views/backend/layout.php:4-6` | AUDIT-H8 | ✅ |
| 9B-9 | Set `$_SESSION['admin_username']` saat login | `controllers/AuthController.php:47` | AUDIT-H9 | ✅ |
| 9B-10 | Cast `$_GET['id']` ke integer | `index.php:54` | AUDIT-H10 | ✅ |
| 9B-11 | Cast `$_POST['id']` ke integer | `index.php:62` | AUDIT-H11 | ✅ |
| 9B-12 | Buat halaman 404 HTML untuk router | `core/Router.php:36-37` | AUDIT-H12 | ✅ |

#### Phase 9C — MEDIUM Fixes (18 task) ✅ SELESAI

| No | Task | File | Temuan | Status |
|----|------|------|--------|--------|
| 9C-1 | Ganti SQL concat ke prepared statement di cleanup | `config/security.php:184` | AUDIT-M1 | ✅ |
| 9C-2 | Panggil `cleanupOrphanedFiles()` saat file dihapus | `controllers/FileController.php:141` | AUDIT-M2 | ✅ |
| 9C-3 | Cast `$item['id']` ke int di news-list | `views/backend/news-list.php:43` | AUDIT-M3 | ✅ |
| 9C-4 | Cast `$stats` ke int di dashboard views | `views/backend/dashboard.php:18,35,52,69` | AUDIT-M4 | ✅ |
| 9C-5 | Tambah null coalescing `$csrf_token ?? ''` di pages-create | `views/backend/pages-create.php:12` | AUDIT-M5 | ✅ |
| 9C-6 | Standarisasi `htmlspecialchars()` → `e()` di semua views | Banyak file | AUDIT-M6 | ✅ |
| 9C-7 | Tambah `PDO::ATTR_TIMEOUT => 5` | `config/database.php:15-19` | AUDIT-M7 | ✅ |
| 9C-8 | Ubah log permissions ke `0750` | `config/logger.php:16` | AUDIT-M8 | ✅ |
| 9C-9 | Tambah log cleanup (>30 hari) | `config/logger.php` | AUDIT-M9 | ✅ |
| 9C-10 | Ganti `$_GET['status']` ke session flash di pages-edit | `views/backend/pages-edit.php:10` | AUDIT-M10 | ✅ |
| 9C-11 | Fix ForceType di storage/uploads/.htaccess — izinkan gambar | `storage/uploads/.htaccess:17-20` | AUDIT-M11 | ✅ |
| 9C-12 | Buat migration 002 idempotent (ADD COLUMN IF NOT EXISTS) | `migrations/002_add_pages_title.sql` | AUDIT-M12 | ✅ |
| 9C-13 | Buat `.env.example` | Project root (baru) | AUDIT-M13 | ✅ |
| 9C-14 | Tambah IDE/vendor/log ke .gitignore | `.gitignore` | AUDIT-M14 | ✅ |
| 9C-15 | Tambah slug uniqueness check | `controllers/NewsController.php:219` | AUDIT-M15 | ✅ |
| 9C-16 | Tambah null check di `editForm()` | `controllers/PageController.php:120` | AUDIT-M16 | ✅ |
| 9C-17 | Render 404 page (bukan silent redirect) | `controllers/NewsController.php:39`, `PageController.php:30` | AUDIT-M17 | ✅ |
| 9C-18 | Ganti GET flash messages ke session di pages-edit | `views/backend/pages-edit.php:10` | AUDIT-M18 | ✅ |

#### Phase 9D — LOW Fixes (15 task) ✅ SELESAI

| No | Task | File | Temuan | Status |
|----|------|------|--------|--------|
| 9D-1 | Evaluasi/hapus dead code `jsonResponse()` | `core/Controller.php:60` | AUDIT-L1 | ✅ Pertahankan (future use) |
| 9D-2 | Panggil atau hapus `cleanupOrphanedFiles()` | `controllers/FileController.php:141` | AUDIT-L2 | ✅ Dipanggil di 9C-2 |
| 9D-3 | Ganti `SELECT *` ke explicit columns di models | `models/News.php`, `models/Page.php` | AUDIT-L3 | ✅ |
| 9D-4 | Tambah extension whitelist di thumbnail upload | `controllers/NewsController.php:224` | AUDIT-L4 | ✅ |
| 9D-5 | Clear session cookie saat logout | `controllers/AuthController.php:60` | AUDIT-L5 | ✅ Dilakukan di 9B-2 |
| 9D-6 | Tambah `EXTR_SKIP` ke `extract()` di Controller | `core/Controller.php:12` | AUDIT-L6 | ✅ |
| 9D-7 | Tambah max length validation di login input | `controllers/AuthController.php:26` | AUDIT-L7 | ✅ |
| 9D-8 | Log hanya path (tanpa query string) | `config/logger.php:22` | AUDIT-L8 | ✅ Dilakukan di 9B-3 |
| 9D-9 | Hapus Bootstrap JS dari login page | `views/frontend/login.php:43` | AUDIT-L9 | ✅ |
| 9D-10 | Standarisasi route definitions | `index.php` | AUDIT-L10 | ✅ Pertahankan (news routes butuh `$_GET`, pattern berbeda tapi functional) |
| 9D-11 | Buat sidebar backend responsive (collapsible) | `views/backend/layout.php:11` | AUDIT-L11 | ✅ Selesai Phase 17-3 (offcanvas mobile) |
| 9D-12 | Evaluasi tambah kolom `admin_users` (email, last_login) | `schema_polinest_baak.sql` | AUDIT-L12 | ✅ Selesai Phase 17-4 (email, is_active, last_login_at) |
| 9D-13 | Evaluasi tambah kolom `news.is_active` | `schema_polinest_baak.sql` | AUDIT-L13 | ✅ Selesai Phase 17-5 (draft/publikasi) |
| 9D-14 | Tambah warning admin password hash di README | `README.md` | AUDIT-L14 | ✅ Warning ditambah di Default Credentials |
| 9D-15 | Pastikan `logWarning()` selalu ter-defined | `views/frontend/news-detail.php:28` | AUDIT-L15 | ✅ Sudah ter-defined via `function_exists()` guard |

#### Phase 10 — Post-Audit Bug Fixes (7 task) ✅ SELESAI

> **UPDATE 27 Juli 2026:** Phase 10 — perbaikan bug dan inkonsistensi yang ditemukan setelah Phase 9.

| No | Task | File | Temuan | Status |
|----|------|------|--------|--------|
| 10-1 | Standarisasi `htmlspecialchars()` → `e()` | `views/frontend/news-detail.php:11` | AUDIT-M6 sisa | ✅ |
| 10-2 | Standarisasi `htmlspecialchars()` → `e()` | `views/backend/pages-create.php:12` | AUDIT-M6 sisa | ✅ |
| 10-3 | Standarisasi `htmlspecialchars()` → `e()` (5 instance) | `views/backend/pages-list.php:31,34,41,48,49` | AUDIT-M6 sisa | ✅ |
| 10-4 | Fix column name mismatch `$p['updated_at']` → `$p['last_updated']` | `views/backend/pages-list.php:37` | BUG — kolom selalu kosong | ✅ |
| 10-5 | Fix `logError()` dipanggil sebelum logger loaded → `error_log()` | `config/database.php:25` | BUG — fatal error fresh install | ✅ |
| 10-6 | Buat `storage/backups/.htaccess` — Deny from all | `storage/backups/.htaccess` (baru) | Missing protection | ✅ |
| 10-7 | Tambah warning admin password hash di README | `README.md` | AUDIT-L14 | ✅ |

---

### 8.3 Phase 11 — Modul Katalog Berita Publik (`/berita`) ✅ SELESAI

> **UPDATE 31 Juli 2026:** Penambahan halaman katalog berita publik (arsip lengkap) + menu navigasi "Berita" di navbar & footer, sebagai tindak lanjut hasil analisis perbandingan dengan portal referensi (baak.gunadarma.ac.id). Tidak ada perubahan pada rute/controller lain yang sudah ada.

| No | Task | File | Status |
|----|------|------|--------|
| 11-1 | Tambah parameter `$keyword` di `News::getAll()` — pencarian judul/isi berita dengan prepared statement (positional `?`, kompatibel native prepares `EMULATE_PREPARES=false`) | `models/News.php` | ✅ |
| 11-2 | Tambah method `NewsController::indexPublic()` — ambil arsip berita + query `?q=` untuk pencarian | `controllers/NewsController.php` | ✅ |
| 11-3 | Tambah route `GET /berita` (sebelum `GET /berita/{slug}` agar tidak konflik) | `index.php` | ✅ |
| 11-4 | Buat view `views/frontend/news-list.php` — grid card responsif, form pencarian, empty state, escaping `e()` di semua output | `views/frontend/news-list.php` (baru) | ✅ |
| 11-5 | Tambah menu "Berita" di navbar & footer publik | `views/frontend/layout.php` | ✅ |

**Catatan teknis Phase 11:**
- Rute `GET /berita` didaftarkan **sebelum** `GET /berita/{slug}` — Router mencocokkan route pertama yang cocok, sehingga `/berita` (tanpa slug) masuk ke katalog, bukan detail.
- Pencarian memakai placeholder positional (`?`) dua kali, bukan named placeholder — karena `config/database.php` menonaktifkan emulasi prepare (`PDO::ATTR_EMULATE_PREPARES => false`), named placeholder ganda akan memicu `HY093 Invalid parameter number` di MySQL/MariaDB.
- Semua output di view baru memakai helper `e()` (konsisten dengan standar Phase 9C-6/10).
- `News::getAll()` backward-compatible: `getAll(6)` di `HomeController::index()` dan `getAll()` di `NewsController::listAdmin()` tetap berfungsi tanpa perubahan.

---

### 8.4 Phase 12 — Halaman Layanan BAAK (Indeks SOP Publik) ✅ SELESAI

> **UPDATE 31 Juli 2026:** Menghubungkan halaman SOP statis (`/halaman/{identifier}`) ke menu navigasi publik, sesuai rencana Tahap 2 (Penguatan Layanan Publik & SOP). Sebelumnya halaman SOP tidak memiliki pintu masuk dari menu — mahasiswa tidak bisa menemukan SOP secara langsung.

| No | Task | File | Status |
|----|------|------|--------|
| 12-1 | Tambah method `PageController::indexPublic()` — ambil seluruh halaman SOP via `Page::getAll()` yang sudah ada | `controllers/PageController.php` | ✅ |
| 12-2 | Tambah route `GET /layanan` (tidak konflik dengan `/halaman/{identifier}` — exact match) | `index.php` | ✅ |
| 12-3 | Buat view `views/frontend/pages-list.php` — grid card SOP, breadcrumb, empty state, escaping `e()` | `views/frontend/pages-list.php` (baru) | ✅ |
| 12-4 | Tambah menu "Layanan BAAK" di navbar & footer publik | `views/frontend/layout.php` | ✅ |

**Catatan teknis Phase 12:**
- `Page::getAll()` sudah ada dan dipakai ulang (tidak ada duplikasi query baru).
- `last_updated` dipakai untuk menampilkan tanggal pembaruan — sesuai kolom aktual di `pages_content` (mengikuti fix Phase 10-4).

---

### 8.5 Phase 13 — Template CSV & Panduan Deployment XAMPP ✅ SELESAI

> **UPDATE 31 Juli 2026:** Persiapan serah terima (Tahap 3): template CSV resmi untuk impor data pembimbing semesteran + dokumentasi optimasi XAMPP untuk beban ±1.000 mahasiswa.

| No | Task | File | Status |
|----|------|------|--------|
| 13-1 | Buat template CSV (`nim, student_name, advisor_name, advisor_type`) dengan 3 contoh baris sesuai format validasi import | `storage/templates/template_dosen_pembimbing.csv` (baru) | ✅ |
| 13-2 | Proteksi folder templates — `Require all denied` (pola sama dengan backups/logs) | `storage/templates/.htaccess` (baru) | ✅ |
| 13-3 | Tambah method `AdvisorController::downloadTemplate()` — requireLogin() + header download CSV yang benar | `controllers/AdvisorController.php` | ✅ |
| 13-4 | Tambah route `GET /admin/import-csv/template` | `index.php` | ✅ |
| 13-5 | Tambah tombol "Unduh Template CSV" di halaman import | `views/backend/advisor-import.php` | ✅ |
| 13-6 | Tambah section "Panduan Deployment Lokal (XAMPP) & Performa" + checklist serah terima di README | `README.md` | ✅ |

**Catatan teknis Phase 13:**
- Template disajikan lewat controller (bukan akses HTTP langsung) — file tetap terproteksi `.htaccess`, hanya admin login yang bisa mengunduh.
- Format CSV mengikuti persis validasi `processImport()`: header lowercase tanpa BOM, kolom `advisor_type` hanya `Wali`/`Magang`/`TA`.
- `.gitignore` TIDAK mengecualikan `storage/templates/` — template adalah file sumber yang wajib ter-commit (berbeda dengan uploads/backups).

---

### 8.6 Phase 14 — Design System Frontend (patokan `politekniknest.design.md`) ✅ SELESAI

> **UPDATE 31 Juli 2026:** Penerapan design tokens dari `politekniknest.design.md` (ekstraksi dari situs resmi https://politekniknest.ac.id/) ke seluruh frontend publik: warna brand (primary `#3a4f66`, accent `#074c84`, surface `#f96d80`), tipografi Montserrat + Roboto, radius, shadow, dan motion.

| No | Task | File | Status |
|----|------|------|--------|
| 14-1 | Buat `assets/css/design-system.css` — design tokens + override Bootstrap 5.3 (CSS variables `--bs-*`) | `assets/css/design-system.css` (baru) | ✅ |
| 14-2 | Update frontend layout: Google Fonts (Montserrat 700/800, Roboto 400/500), link design-system.css, topbar kontak, class `navbar-nest` & `footer-nest` | `views/frontend/layout.php` | ✅ |
| 14-3 | Restyle hero beranda (`hero-nest` — gradient brand + badge surface + shadow elevated) & section title + tombol "Lihat Semua" ke `/berita` | `views/frontend/home.php` | ✅ |
| 14-4 | Restyle halaman login (icon dalam lingkaran brand, fonts, CSS link) | `views/frontend/login.php` | ✅ |
| 14-5 | Whitelist Google Fonts di CSP — `style-src` + `https://fonts.googleapis.com`, `font-src` + `https://fonts.gstatic.com` | `config/security.php:43,45` | ✅ |

**Catatan teknis Phase 14:**
- Pendekatan utama: **override variabel Bootstrap 5.3** (`--bs-primary`, `--bs-secondary`, `--bs-dark`, `--bs-body-font-family`, dll.) — seluruh komponen (btn-primary, text-primary, bg-dark, badge, form) ter-restyle otomatis tanpa mengubah markup view lain → risiko rendah, tidak menyentuh file backend.
- `text-secondary` kini mengikuti token `text-muted: #074c84` (biru tua) — konsisten dengan situs resmi.
- Google Fonts di-load via `<link>` sehingga wajib masuk whitelist CSP (pola sama seperti AUDIT-C1/9A-1: CDN whitelist).
- **Tidak ada perubahan pada ID/class yang dipakai JavaScript** (`form-cari-dosen`, `input-nim`, `input-nama`, `btn-submit-cari`, `container-hasil-pencarian`, `table-body-hasil`, `alert-pesan-error`) — pencarian AJAX tetap berfungsi.
- Card memakai shadow token `rgba(0,0,0,0.1) 5px 5px 20px` + hover lift (elevated shadow) dengan easing token.
- View lain (`news-list`, `pages-list`, `news-detail`, `page-detail`, `search-dosen`, `jadwal`) tidak diubah markup-nya — ter-restyle otomatis via CSS variables.

---

### 8.7 Phase 15 — Fix Login Admin (tidak bisa login admin/admin) ✅ SELESAI

> **UPDATE 31 Juli 2026:** Investigasi penyebab tidak bisa login dengan kredensial default. Ditemukan 3 penyebab potensial pada database lama (yang tidak ter-cover script migrasi gabungan, karena script tersebut sengaja TIDAK menyentuh `admin_users`):

| Penyebab | Detail |
|----------|--------|
| **Hash `$2b$` tidak didukung PHP** | Hash di `schema_polinest_baak.sql` ber-prefix `$2b$` (format bcrypt Node.js). `password_verify()` PHP hanya mendukung `$2a$`/`$2y$` → selalu `false` meskipun password benar. |
| **Tabel `admin_users` tidak ada** | Database lama yang dibangun sebelum fitur login belum tentu punya tabel ini — script migrasi gabungan tidak membuatnya. |
| **Rate limit login terkunci** | `AuthController::login()` membatasi 8 percobaan/5 menit per IP & 5 percobaan/15 menit per username — percobaan berulang bisa mengunci login. |

| No | Task | File | Status |
|----|------|------|--------|
| 15-1 | Buat tool CLI `tools/reset_admin_password.php` — idempotent, memperbaiki 3 penyebab di atas (create table if missing, konversi `$2b$`→`$2y$`, reset hash admin/admin dengan `password_hash()`, bersihkan rate limit login) | `tools/reset_admin_password.php` (baru) | ✅ |
| 15-2 | Block akses web ke folder `tools/` di root `.htaccess` (defense-in-depth, pola sama dengan config/models/migrations) | `.htaccess` | ✅ |

**Cara pakai (wajib CLI, bukan browser):**
```bash
php tools/reset_admin_password.php
```
Lalu login dengan `admin` / `admin` dan **segera ganti password**.

**Catatan teknis Phase 15:**
- Tool memakai positional placeholder (`?`) untuk `INSERT ... ON DUPLICATE KEY UPDATE` — aman dengan `PDO::ATTR_EMULATE_PREPARES => false` (native prepares; named placeholder ganda akan error HY093).
- Konversi `$2b$`→`$2y$` memakai `SUBSTRING(password, 5)` — memotong prefix 4 karakter, sisa hash (cost+base64) tetap utuh.
- Tool hanya menyentuh `admin_users` + baris rate limit endpoint `login` — tidak mengubah tabel lain.
- Akses web ke tool diblokir ganda: guard `PHP_SAPI !== 'cli'` di dalam script + `RewriteRule` block di `.htaccess`.

> **TINDAK LANJUT 31 Juli 2026 (dari dump DB user):** Root cause terkonfirmasi — dump `admin_users` berisi hash `$2b$10$...` (bcrypt Node.js) yang tidak didukung `password_verify()` PHP. Perbaikan:
> - User menjalankan `UPDATE admin_users SET password = CONCAT('$2y$', SUBSTRING(password, 5)) WHERE username = 'admin' AND password LIKE '$2b$%'` di phpMyAdmin → login berhasil.
> - **`schema_polinest_baak.sql` diperbarui** — hash default admin diganti dari `$2b$` → `$2y$` (prefix kompatibel PHP) agar fresh install tidak mengalami bug yang sama.

---

### 8.8 Phase 16 — Kompatibilitas Legacy MD5 + Auto-Upgrade bcrypt ✅ SELESAI

> **UPDATE 31 Juli 2026:** Permintaan agar sistem dapat membaca hash MD5 untuk password admin. Demi keamanan, MD5 diimplementasikan sebagai **jembatan sementara** (bukan dukungan permanen): login via MD5 berhasil, tetapi hash langsung di-upgrade ke bcrypt pada login sukses pertama (pola standar "upgrade-on-login"). MD5 tidak akan selamanya tersimpan di database.

| No | Task | File | Status |
|----|------|------|--------|
| 16-1 | Tambah `Admin::updatePassword(int $id, string $hash)` — prepared statement positional | `models/Admin.php` | ✅ |
| 16-2 | `AuthController::login()` — prioritas bcrypt, fallback MD5 (32 hex, `hash_equals` timing-safe), auto-upgrade ke bcrypt + `logWarning()` | `controllers/AuthController.php:45-67` | ✅ |

**Catatan teknis Phase 16:**
- Urutan verifikasi: **bcrypt dulu** → baru MD5. Hash MD5 yang valid harus persis 32 karakter hex (`md5()` output) dan dibandingkan dengan `hash_equals()` (timing-safe).
- Setelah login sukses via MD5, `updatePassword()` mengganti hash DB ke bcrypt (`password_hash(PASSWORD_BCRYPT)`), lalu log warning mencatat kejadian upgrade.
- MD5('admin') = `21232f297a57a5a743894a0e4a801fc3` (diverifikasi via PowerShell).
- Saran keamanan: gunakan ini hanya untuk situasi darurat (mis. lupa password & butuh akses cepat), lalu segera loginkan sekali agar hash ter-upgrade — jangan biarkan hash MD5 menetap di DB.

---

### 8.9 Phase 17 — Sinkronisasi Update 3 Agustus + Sisa Tugas ✅ SELESAI

> **UPDATE 05 Agustus 2026:** Dua hal:
> 1. **Sinkronisasi dokumen** dengan update teman tim tanggal 3 Agustus (commit `c1d4eb4` Bayu San & `afe8831` KaYeYe) yang belum tercatat di dokumen ini.
> 2. **Menyelesaikan semua sisa tugas** yang sebelumnya ditandai Partial/Skip (PRD No.12, AUDIT-L11/L12/L13).

#### Rekap Update Teman Tim (3 Agustus 2026) — sudah disinkronkan

| Perubahan | File | Keterangan |
|-----------|------|------------|
| Auto-setup DB + tabel + akun admin | `config/setup.php` (baru), `index.php` | `ensureAppReady()` idempotent tiap request; seed admin/admin bcrypt runtime |
| Normalisasi link konten rich text | `config/security.php` | `sanitizeHtmlContent()` + cache md5 per request + `normalizeRichContentLinks()` (href tanpa protokol diberi https://, link eksternal target=_blank rel=noopener) |
| Layout frontend pada halaman 404/detail | `controllers/NewsController.php`, `PageController.php` | Param `'frontend'` di `render()` |
| Atomic swap CSV staging (TEMPORARY → reguler) | `models/Advisor.php` | `RENAME TABLE` dua tabel satu perintah + recovery; catatan: TEMPORARY tidak bisa di-RENAME ke tabel permanen |
| `fgetcsv` length `-1` → `null` | `controllers/AdvisorController.php` | Setara (tanpa batas) |
| Dummy data dosen pembimbing | `dummy_dosen_pembimbing.csv` (baru) | 22 baris, format validasi |
| Chore: hapus PDF upload test dari tracking | `afe8831` | Sudah masuk .gitignore; file `current_*.txt` ditambahkan (dump debug) |

#### Tugas yang Diselesaikan (05 Agustus 2026)

| No | Task | File | Status |
|----|------|------|--------|
| 17-1 | Perbaiki PRD No.12 — Situs RPS: konstanta `RPS_URL` (default + env var `RPS_URL`), kartu link keluar di halaman Jadwal & Pedoman | `config/constants.php`, `views/frontend/jadwal.php`, `.env.example` | ✅ |
| 17-2 | Update README — daftar migration 003 & 004 | `README.md` | ✅ |
| 17-3 | AUDIT-L11 — Sidebar backend responsive: sidebar desktop (`d-none d-lg-block`) + offcanvas mobile + topbar hamburger; item menu di-loop dari array `$sidebarItems` (tidak duplikat) | `views/backend/layout.php` | ✅ |
| 17-4 | AUDIT-L12 — Kolom `admin_users`: `email`, `is_active`, `last_login_at` (schema + migration idempotent + auto-upgrade `ensureAppSchemaColumns()`); `AuthController` cek `is_active` saat login (default aktif untuk DB lama) + `Admin::updateLastLogin()` (try/catch aman DB lama) | `schema_polinest_baak.sql`, `migrations/003_admin_users_extra.sql` (baru), `config/setup.php`, `models/Admin.php`, `controllers/AuthController.php`, `tools/reset_admin_password.php` | ✅ |
| 17-5 | AUDIT-L13 — Kolom `news.is_active` (draft/publikasi): schema + migration idempotent + auto-upgrade; `News::getAll()/getBySlug()` param `$activeOnly` (publik hanya berita aktif; admin & generateSlug tetap lihat semua); checkbox "Publikasikan" di form; badge Status Aktif/Draft di list; `HomeController` & katalog hanya berita aktif | `schema_polinest_baak.sql`, `migrations/004_news_is_active.sql` (baru), `config/setup.php`, `models/News.php`, `controllers/NewsController.php`, `controllers/HomeController.php`, `views/backend/news-form.php`, `views/backend/news-list.php` | ✅ |

**Catatan teknis Phase 17:**
- **Auto-upgrade kolom** (`ensureAppSchemaColumns`): instalasi lama otomatis mendapat kolom baru saat request berikutnya (cek `INFORMATION_SCHEMA.COLUMNS`, per kolom, try/catch per kolom) — tanpa perlu import manual, dan tidak mengunci aplikasi jika gagal.
- **Keamanan akun nonaktif**: login akun `is_active = 0` ditolak dengan pesan generik (tidak membocorkan bahwa akun ada). Kolom belum ada di DB lama → dianggap aktif (`?? 1`), tidak mengunci akses.
- **`updateLastLogin()`** dibungkus try/catch — DB lama tanpa kolom tidak crash, hanya error_log.
- **Backward-compatible**: `News::getAll()` / `getBySlug()` tanpa argumen baru berperilaku persis seperti sebelumnya (admin list & generateSlug tidak berubah).
- UI pengelolaan akun admin (membuat admin kedua, edit email, toggle nonaktif) **belum ada** — pengaturan via SQL/phpMyAdmin. Disarankan sebagai fitur masa depan.

---

### Timeline Estimasi

| Phase | Jam | Dependencies | Target |
|-------|-----|-------------|--------|
| Phase 5–8 (sudah selesai) | 9–13 jam | — | ✅ Selesai |
| Phase 9A — CRITICAL | 1–2 jam | Tidak ada | ✅ Selesai |
| Phase 9B — HIGH | 2–3 jam | Phase 9A | ✅ Selesai |
| Phase 9C — MEDIUM | 3–4 jam | Phase 9A+9B | ✅ Selesai |
| Phase 9D — LOW | 2–3 jam | Phase 9A+9B | ✅ Selesai |
| Phase 10 — Post-Audit | 0.5 jam | Phase 9 | ✅ Selesai |
| Phase 11 — Katalog Berita Publik | ~1–2 jam | Phase 1–10 | ✅ Selesai |
| Phase 12 — Layanan BAAK (SOP Index) | ~1 jam | Phase 1–10 | ✅ Selesai |
| Phase 13 — Template CSV + Panduan XAMPP | ~1 jam | Phase 1–10 | ✅ Selesai |
| Phase 14 — Design System Frontend | ~2–3 jam | Phase 1–13 | ✅ Selesai |
| Phase 15 — Fix Login Admin | ~0.5 jam | — | ✅ Selesai |
| Phase 16 — Legacy MD5 + Auto-Upgrade | ~0.5 jam | Phase 15 | ✅ Selesai |
| Phase 17 — Sinkronisasi + Sisa Tugas | ~2 jam | Phase 1–16 | ✅ Selesai |
| **Total Phase 9–17** | **18–25 jam** | — | — |

### Total Estimasi Proyek

| Komponen | Jam |
|----------|-----|
| Dev 1 — Core & Auth | ~8 jam (sudah selesai) |
| Dev 2 — Content Delivery | ~10 jam (sudah selesai) |
| Dev 3 — Search & Security (Phase 1–4) | ~17 jam (sudah selesai) |
| AI Assistant (Phase 5–8) | ~9–13 jam (sudah selesai) |
| Phase 9 — Security Audit Fixes | 8–12 jam (sudah selesai) |
| Phase 10 — Post-Audit Bug Fixes | ~0.5 jam (sudah selesai) |
| Phase 17 — Sinkronisasi & Sisa Tugas | ~2 jam (sudah selesai) |
| **Grand Total** | **54–62.5 jam** |

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
   mysql -u root -p polinest_baak < migrations/002_add_pages_title.sql
   ```

4. **Setup directory permissions**
   ```bash
   chmod -R 755 storage/
   chown -R www-data:www-data storage/
   ```

5. **Buat `.env` file (atau set di config/database.php)**
   ```
   APP_ENV=production
   DB_HOST=127.0.0.1
   DB_NAME=polinest_baak
   DB_USER=root
   DB_PASS=<password>
   ```

6. **Pastikan `.htaccess` aktif**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
   ```

7. **Test login**
   Buka `/login` → login dengan admin/admin → ganti password segera!

8. **PENTING: Setelah Phase 9 selesai** — pastikan:
   - `.htaccess` root punya `Options -Indexes` dan block path terlarang
   - `config/.htaccess` dan `storage/logs/.htaccess` ada dengan `Deny from all`
   - DB credentials tidak hardcoded

---

## 10. Appendix: Detail Teknis

### A. Security Layer (config/security.php)

| Fungsi | Status | Keterangan |
|--------|--------|------------|
| `generateCsrfToken()` | ✅ | Token 64-char hex, timing-safe |
| `regenerateCsrfToken()` | ✅ | Regen setelah form submit |
| `verifyCsrfToken()` | ✅ | hash_equals() — timing-safe |
| `generateCspNonce()` | ✅ | Nonce 32-byte hex per session |
| `emit_security_headers()` | ✅ | CSP nonce + whitelist CKEditor/Google Fonts + HSTS conditional |
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
  11. Atomic swap via staging table REGULER (sejak 3 Agu — TEMPORARY
      tidak bisa di-RENAME menjadi tabel permanen):
      a. DROP TABLE IF EXISTS tmp_student_advisors
      b. CREATE TABLE tmp_student_advisors LIKE student_advisors
      c. INSERT all rows (dengan normalize)
      d. RENAME TABLE student_advisors TO student_advisors_old,
                   tmp_student_advisors TO student_advisors   (satu perintah)
      e. DROP TABLE IF EXISTS student_advisors_old
      Recovery jika gagal: kembalikan student_advisors_old jika tabel utama hilang,
      hapus sisa staging — data tidak pernah hilang.
  12. Regenerate CSRF token
  13. Redirect with status=success
```

---

> **Dokumen ini adalah acuan tunggal untuk seluruh tim development.**
> Setiap perubahan scope wajib dicatat di dokumen ini (update versi + log keputusan), sesuai §5A PRD.md.

---

## 11. Update Terbaru — Fase 17.x (12 Agustus 2026)

> **Trigger:** permintaan klien (Pak Dimas) — (1) admin kasih judul & kategori kustom pada file unduhan, (2) import data dosen pembimbing pakai file **Excel per-tabel** (1 sheet, beberapa tabel), bukan CSV datar. Prioritas: tuntasakan SOP Cuti + Form Cuti dulu.

### 11.1 Investigasi SOP Cuti (backend "nanggung" — terkait phpMyAdmin) ✅ SELESAI
- `schema_polinest_baak.sql:101` (dump phpMyAdmin) men-seed halaman `sop-cuti` hanya dengan konten **tes** `<p>TES FITUR&nbsp;</p>`.
- Form Cuti (PRD No.10) ditandai ✅ tapi belum ada kategori/kategori file maupun file PDFnya.
- **Perbaikan:** seed SOP Cuti diganti dengan konten SOP Akademik template asli (definisi, syarat, prosedur, berkas, kontak). Form Cuti disiapkan via kategori `form_cuti` (lihat 11.2) — file PDF resmi di-upload via UI begitu tersedia.

### 11.2 Judul & Kategori Kustom File Unduhan ✅ SELESAI
- `migrations/007_downloadable_files_title.sql` — kolom `title` (idempotent).
- `models/DownloadableFile.php`: `addFile/getById/getActiveFiles` bawa `title`; baru `getDistinctCategories()`.
- `controllers/FileController.php`: validasi kategori kustom `^[a-z0-9_]{1,100}$`, `SUGGESTED_CATEGORIES` termasuk `form_cuti`/`form_pindah_kelas`/`form_mengundurkan_diri`.
- `views/backend/files-manage.php`: input kategori + datalist + field Judul Dokumen + kolom Judul di tabel.
- `views/frontend/jadwal.php`: label `form_cuti`/`form_pindah_kelas`/`form_mengundurkan_diri` + judul publik.

### 11.3 Form Edit Halaman — Bisa Ganti Judull ✅ SELESAI
- `views/backend/pages-edit.php` + `models/Page.php` `updateContent()` (param `$title` opsional) + `PageController::save()`.

### 11.4 Fix Bug Tampilan (style commit 12 Agu) ✅ SELESAI
- `views/frontend/page-detail.php` — tag `<h1>` rusak (judul SOP Cuti) dibetulkan.
- `views/frontend/news-detail.php` — `</div>` penutup `row` yang hilang.

### 11.5 Import Excel Per-Tabel (native PHP, tanpa Composer) ✅ SELESAI
- `libs/XlsxReader.php` baru — parser `.xlsx` pakai `ZipArchive` + `SimpleXML`; membaca sel inline string & shared string, namespace-agnostic via `local-name()`.
- `controllers/AdvisorController.php`: refaktor `processImport()` → `parseCsvRows()` + `parseXlsxRows()`; XLSX menerima **1 sheet berisi beberapa tabel** (header tiap tabel `nim,student_name, advisor_name, advisor_type`; baris judul/pemisah dilewati).
- `index.php`: route `GET /admin/import-csv/template-xlsx` + `AdvisorController::downloadTemplateXlsx()`.
- `storage/templates/template_dosen_pembimbing.xlsx` baru (3 tabel contoh; XML well-formedness & XPath disimulasikan via PowerShell — belum di-execute dengan PHP CLI).
- `views/backend/advisor-import.php`: accept `.csv,.xlsx` + petunjuk format per-tabel.

### 11.6 Daftar File Berubah/Ditambah
**Diubah (11):** `README.md`, `config/setup.php`, `controllers/AdvisorController.php`, `controllers/FileController.php`, `controllers/PageController.php`, `index.php`, `models/DownloadableFile.php`, `models/Page.php`, `schema_polinest_baak.sql`, `views/backend/advisor-import.php`, `views/backend/files-manage.php`, `views/backend/pages-edit.php`, `views/frontend/jadwal.php`, `views/frontend/news-detail.php`, `views/frontend/page-detail.php`
**Baru (4):** `libs/XlsxReader.php`, `migrations/006_seed_sop_pages.sql`, `migrations/007_downloadable_files_title.sql`, `storage/templates/template_dosen_pembimbing.xlsx`

### 11.7 Verifikasi
- `php -l` belum dijalankan di mesin dev ini (`php.exe` portabel rusak — exit `0xC0000135` DLL missing); segera jalankan `php -l` pada tiap file `.php` di atas pakai PHP XAMPP (`C:\xampp\php\php.exe`).
- **Todo manual terakhir:** jalankan migration 006/007, upload file kategori `form_cuti`+judul → cek tampil di `/jadwal`; import `template_dosen_pembimbing.xlsx` → cek `student_advisors` terisi. Masukkan file PDF resmi Form Cuti via UI.

### 11.8 Catatan / Follow-up
- UI kelola akun admin (buat/edit admin, toggle non-aktif) **belum ada** — via SQL/phpMyAdmin (sesuai catatan Phase 17).
- `files_upload/` (5 PDF dummy ~14 KB) tidak terpakai aplikasi — **saring/replace** secara terpisah (di luar fokus SOP Cuti).
