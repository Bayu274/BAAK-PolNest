# NOTE — Perubahan Tim (Sinkronisasi & Penyelesaian Sisa Tugas)

> **Tanggal:** 05 Agustus 2026
> **Referensi:** todo.md §8.9 (Phase 17) + Rekap Update 3 Agustus
> **Branch:** development

Dokumen ini mencatat seluruh perubahan yang dilakukan sesi ini, supaya developer lain tahu betul apa yang berubah, kenapa, dan apa yang harus dilakukan. Perubahan ini **tidak menambahkan fitur baru** selain menuntaskan tugas lama (PRD No.12, AUDIT-L11/L12/L13) yang sebelumnya ditandai Partial/Skip di todo.md.

---

## 1. Rekap Update Teman Tim (3 Agustus 2026) — Sudah Disinkronkan

Commit `c1d4eb4` (Bayu San) dan `afe8831` (KaYeYe) sudah dipelajari dan dicatat di todo.md. Ringkasan:

| Perubahan | File | Keterangan |
|-----------|------|------------|
| Auto-setup DB + tabel + akun admin | `config/setup.php` (baru), `index.php` | `ensureAppReady()` idempotent; seed admin/admin bcrypt runtime; **tidak menyentuh data existing** |
| Normalisasi link konten rich text | `config/security.php` | `sanitizeHtmlContent()` + cache md5 per request + `normalizeRichContentLinks()` (href tanpa protokol diberi `https://`, link eksternal `target="_blank" rel="noopener noreferrer"`) |
| Layout frontend pada halaman 404/detail | `NewsController.php`, `PageController.php` | Param `'frontend'` pada `render()` |
| Atomic swap CSV (TEMPORARY → tabel reguler) | `models/Advisor.php` | `RENAME TABLE` dua tabel dalam satu perintah + recovery; TEMPORARY tidak bisa di-RENAME ke tabel permanen |
| `fgetcsv` length `-1` → `null` | `controllers/AdvisorController.php` | Setara (tanpa batas) |
| Dummy data dosen pembimbing | `dummy_dosen_pembimbing.csv` (baru) | 22 baris format validasi |
| Chore tracking | `afe8831` | PDF upload test dihapus dari tracking (sudah di .gitignore); tambah dump debug `current_*.txt` |

---

## 2. Perubahan yang Kita Lakukan (05 Agustus 2026)

### 2.1 PRD No.12 — Situs RPS (link keluar) ✅

| File | Perubahan |
|------|-----------|
| `config/constants.php` | Tambah konstanta `RPS_URL` — default `https://rps.politekniknest.ac.id/`, bisa di-override via env `RPS_URL` |
| `views/frontend/jadwal.php` | Tambah kartu "Situs RPS" (link keluar, `target="_blank" rel="noopener noreferrer"`) di grid dokumen |
| `.env.example` | Dokumentasi `RPS_URL` |

> **Untuk developer:** sesuaikan `RPS_URL` ke alamat RPS resmi kampus (default placeholder).

### 2.2 AUDIT-L11 — Sidebar Backend Responsive ✅

`views/backend/layout.php` ditulis ulang:
- Sidebar desktop: `d-none d-lg-block` (tampil di layar ≥ lg).
- Mobile: sidebar jadi **Bootstrap Offcanvas** (`#sidebarOffcanvas`) + topbar hamburger (`d-lg-none`).
- Item menu di-loop dari array `$sidebarItems` (satu sumber, tidak ada duplikasi markup).
- Link di offcanvas menutup otomatis saat diklik (`data-bs-dismiss="offcanvas"`).
- Tidak ada perubahan rute/controller; ID/class lain tidak disentuh.

> **Untuk developer:** tidak ada yang perlu dilakukan — Bootstrap bundle JS sudah ada di layout.

### 2.3 AUDIT-L12 — Kolom Akun Admin (`email`, `is_active`, `last_login_at`) ✅

| File | Perubahan |
|------|-----------|
| `migrations/003_admin_users_extra.sql` | **BARU** — tambah 3 kolom idempotent (pola INFORMATION_SCHEMA, sama seperti migration 002) |
| `schema_polinest_baak.sql` | `admin_users` kini punya `email` (varchar 100 NULL), `is_active` (tinyint 1 default 1), `last_login_at` (timestamp NULL) |
| `config/setup.php` | Skema fresh install diperbarui + fungsi baru `ensureAppSchemaColumns()` — auto-upgrade kolom untuk instalasi lama (per kolom, try/catch, tidak menyentuh data) |
| `models/Admin.php` | Method baru `updateLastLogin(int $id)` — try/catch, aman di DB lama |
| `controllers/AuthController.php` | Login sekarang cek `is_active`: akun `is_active = 0` **ditolak** (pesan generik, tidak membocorkan bahwa akun ada). Kolom belum ada di DB lama → dianggap aktif (`?? 1`). Setelah login sukses, `last_login_at` di-update |
| `tools/reset_admin_password.php` | `CREATE TABLE IF NOT EXISTS` admin_users disamakan dengan skema baru |

> **Untuk developer:** DB lama di-upgrade otomatis saat request berikutnya via `ensureAppSchemaColumns()` — TIDAK perlu import manual. Kalau ingin manual: `mysql -u root -p polinest_baak < migrations/003_admin_users_extra.sql`
>
> ⚠️ UI kelola akun admin (buat admin baru, edit email, toggle nonaktif) **belum ada** — pengaturan via SQL/phpMyAdmin.

### 2.4 AUDIT-L13 — Draft Berita (`news.is_active`) ✅

| File | Perubahan |
|------|-----------|
| `migrations/004_news_is_active.sql` | **BARU** — tambah `is_active` tinyint(1) default 1 (idempotent) |
| `schema_polinest_baak.sql` | `news` kini punya `is_active` |
| `config/setup.php` | Skema fresh install + auto-upgrade `news.is_active` |
| `models/News.php` | `getAll($limit, $keyword, $activeOnly = false)` dan `getBySlug($slug, $activeOnly = false)` — **backward-compatible**; `create()`/`update()` ikut menyimpan `is_active`; `getById()` mengembalikan `is_active` |
| `controllers/NewsController.php` | `indexPublic()` & `show()` pakai filter aktif (`true`); `store()`/`update()` membaca checkbox `is_active` |
| `controllers/HomeController.php` | Feed beranda `getAll(6, null, true)` — hanya berita aktif |
| `views/backend/news-form.php` | Checkbox "Publikasikan" (di bawah judul); tombol simpan diubah jadi "Simpan Berita" |
| `views/backend/news-list.php` | Kolom baru "Status" (badge **Aktif** hijau / **Draft** abu) |

Aturan penting:
- **Publik** (beranda, `/berita`, `/berita/{slug}`) hanya melihat `is_active = 1`.
- **Admin list** melihat semua (termasuk draft).
- **`generateSlug()` tetap cek SEMUA berita** (default `$activeOnly = false`) supaya slug draft tidak bentrok.

> **Untuk developer:** DB lama di-upgrade otomatis. Manual: `mysql -u root -p polinest_baak < migrations/004_news_is_active.sql`

### 2.5 Dokumentasi

| File | Perubahan |
|------|-----------|
| `README.md` | Daftar migration bertambah 003 & 004 |
| `todo.md` | Disinkronkan penuh: rekap update 3 Agu (section 1), inventaris file, matrix PRD (No.12 → ✅), security XSS → ✅, Open Decisions → ✅, 9D-11/12/13 → ✅, **Phase 17 baru** (§8.9), timeline, Appendix A & E diperbarui |

---

## 3. Yang Harus Kamu Lakukan (Checklist Developer Lain)

1. **Pull branch development** — lalu cek `git log` untuk `c1d4eb4` (update fitur admin) dan perubahan ini.
2. **Database:** tidak perlu apa-apa jika menjalankan lewat `index.php` (auto-setup & auto-upgrade kolom jalan otomatis). Untuk environment tanpa Apache/PHP request: jalankan
   ```bash
   mysql -u root -p polinest_baak < migrations/003_admin_users_extra.sql
   mysql -u root -p polinest_baak < migrations/004_news_is_active.sql
   ```
3. **Uji manual yang disarankan:**
   - Login admin → dashboard → buka `/admin/news` → buat berita baru tanpa centang "Publikasikan" → cek tidak muncul di beranda & `/berita`, tapi muncul di admin list dengan badge Draft.
   - Ubah `RPS_URL` di `.env` (atau biarkan default) → buka `/jadwal` → kartu "Situs RPS" terbuka di tab baru.
   - Login via browser mobile (atau devtools responsive) → sidebar admin jadi menu hamburger.
   - (Opsional) `UPDATE admin_users SET is_active = 0 WHERE username = 'admin'` → login ditolak → kembalikan ke 1.
4. **Syntax check** jika ada PHP CLI lokal: `php -l` pada file yang diubah (list di bawah).

## 4. Daftar File yang Diubah/Ditambah

**Diubah (16):** `.env.example`, `README.md`, `config/constants.php`, `config/setup.php`, `controllers/AuthController.php`, `controllers/HomeController.php`, `controllers/NewsController.php`, `models/Admin.php`, `models/News.php`, `schema_polinest_baak.sql`, `todo.md`, `tools/reset_admin_password.php`, `views/backend/layout.php`, `views/backend/news-form.php`, `views/backend/news-list.php`, `views/frontend/jadwal.php`

**Baru (2):** `migrations/003_admin_users_extra.sql`, `migrations/004_news_is_active.sql`

## 5. Catatan Keamanan & Kompatibilitas

- Semua akses kolom baru memakai fallback (`?? 1`) / try-catch → **tidak ada fatal error di DB lama**.
- Tidak ada perubahan pada: router (`index.php` routes), `config/security.php`, model `Advisor`, `FileController`, view frontend lain — hanya yang tercantum di atas.
- Kolom baru ditambahkan idempotent — aman dijalankan berulang kali, data tidak pernah ditimpa.
