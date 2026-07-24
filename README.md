# Portal Informasi BAAK — Politeknik Nest

Portal informasi publik untuk Biro Administrasi & Akademik Kampus (BAAK) Politeknik Nest, Sukoharjo.

## Tech Stack

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.2 (Native, tanpa framework) |
| Database | MariaDB 10.4+ / MySQL 5.7+ |
| Frontend | Bootstrap 5.3.3 (CDN) |
| Rich Text | CKEditor 5 Classic (CDN) |
| AJAX | Fetch API (vanilla JS) |
| Security | HTMLPurifier 4.15.0 |

## Prerequisites

- PHP 8.2+ dengan extensi: PDO, pdo_mysql, mbstring, fileinfo, json, session
- MariaDB 10.4+ atau MySQL 5.7+
- Web server (Apache + mod_rewrite, atau Nginx + PHP-FPM)

## Installation

```bash
# 1. Clone repository
git clone <repo-url> baak-polnest
cd baak-polnest

# 2. Import database schema
mysql -u root -p polinest_baak < schema_polinest_baak.sql

# 3. Jalankan migration (jika ada data existing)
mysql -u root -p polinest_baak < migrations/001_lower_existing_data.sql
mysql -u root -p polinest_baak < migrations/002_add_pages_title.sql

# 4. Setup directory permissions
chmod -R 755 storage/

# 5. Konfigurasi database credentials
# Edit config/database.php — sesuaikan host, dbname, username, password

# 6. Test login
# Buka /login → admin/admin → ganti password segera!
```

## Folder Structure

```
BAAK-PolNest/
├── index.php                  # Front controller & routing
├── config/
│   ├── constants.php          # BASE_URL, BASE_PATH, APP_ENV
│   ├── database.php           # PDO singleton
│   ├── logger.php             # Logging functions
│   └── security.php           # CSRF, rate limit, CSP, HTMLPurifier
├── core/
│   ├── Controller.php         # Base class (render, requireLogin, jsonResponse)
│   └── Router.php             # Custom URL router
├── controllers/
│   ├── AuthController.php     # Login/logout
│   ├── DashboardController.php # Admin dashboard
│   ├── NewsController.php     # CRUD berita
│   ├── PageController.php     # CRUD halaman
│   ├── FileController.php     # Upload/download file
│   ├── AdvisorController.php  # Pencarian dosen + CSV import
│   └── HomeController.php     # Halaman publik
├── models/
│   ├── Admin.php              # User admin
│   ├── News.php               # Berita
│   ├── Page.php               # Halaman konten
│   ├── DownloadableFile.php   # File PDF/DOCX
│   └── Advisor.php            # Data dosen pembimbing
├── views/
│   ├── frontend/              # Halaman publik
│   │   ├── layout.php         # Layout wrapper (navbar + footer)
│   │   ├── home.php           # Landing page
│   │   ├── search-dosen.php   # Pencarian dosen AJAX
│   │   ├── jadwal.php         # Jadwal & pedoman
│   │   ├── news-detail.php    # Detail berita
│   │   ├── page-detail.php    # Detail halaman
│   │   └── login.php          # Login admin
│   └── backend/               # Admin panel
│       ├── layout.php         # Admin sidebar + wrapper
│       ├── dashboard.php      # Dashboard statistik
│       ├── news-*.php         # CRUD berita views
│       ├── pages-*.php        # CRUD halaman views
│       ├── files-manage.php   # Manajemen file
│       └── advisor-import.php # Import CSV
├── assets/
│   └── js/search-dosen.js     # AJAX search logic
├── storage/
│   ├── uploads/               # File upload (PDF/DOCX/thumbnail)
│   └── backups/               # CSV backup sebelum import
├── migrations/
│   ├── 001_lower_existing_data.sql
│   └── 002_add_pages_title.sql
├── libs/
│   └── htmlpurifier-4.15.0-standalone/
├── schema_polinest_baak.sql   # Database schema
└── .htaccess                  # URL rewriting
```

## Features

- **Pencarian Dosen Pembimbing** — AJAX exact match NIM + Nama
- **CRUD Berita** — Create, read, update, delete + thumbnail upload
- **Manajemen Halaman** — Rich text editor (CKEditor 5)
- **Upload File** — PDF/DOCX dengan validasi berlapis
- **Import CSV** — Data dosen pembimbing dengan atomic swap
- **Dashboard Admin** — Statistik dan aksi cepat
- **Security** — CSRF, rate limiting, CSP nonce, HTMLPurifier

## Default Credentials

| Username | Password |
|----------|----------|
| admin | admin |

> **Penting:** Ganti password segera setelah login pertama kali!

## License

Proprietary — Politeknik Nest, Sukoharjo
