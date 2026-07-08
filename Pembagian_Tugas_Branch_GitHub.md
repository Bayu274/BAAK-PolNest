# Pembagian Tugas Rinci & Branch GitHub — Portal BAAK Politeknik Nest

**Catatan status:** dokumen ini menggabungkan rincian tugas dari draft asli + rebalancing yang dibahas sebelumnya (pindah `pages_content` ke Dev 2, tambah CSV import + cleanup rate limit ke Dev 3). Penamaan function di bawah ini keputusan tim (Bagian 5A PRD — tidak perlu approval klien per-item), tapi kalau kalian sudah punya konvensi sendiri, sesuaikan saja — yang penting konsisten di seluruh tim sebelum mulai coding.

---

## 1. Struktur Branch GitHub

```
main                          # produksi/stabil, hanya di-merge dari development
└── development                # branch integrasi, tempat semua feature branch masuk
    ├── feature/core-setup         → Dev 1
    ├── feature/auth-login          → Dev 1
    ├── feature/news-crud            → Dev 2
    ├── feature/pages-content        → Dev 2
    ├── feature/advisor-search       → Dev 3
    ├── feature/csv-import           → Dev 3
    ├── feature/rate-limiting        → Dev 3
    └── feature/downloadable-files   → Dev 3
```

**Aturan tambahan di luar draft awal:**
- Satu branch = satu domain fungsional (bukan satu branch untuk semua tugas satu orang). Alasan: PR jadi lebih kecil dan lebih gampang direview, dan kalau satu fitur belum selesai tapi fitur lain sudah, yang sudah selesai bisa di-merge duluan tanpa nunggu semuanya kelar.
- `feature/core-setup` dan `feature/auth-login` **wajib** di-merge ke `development` sebelum branch manapun milik Dev 2/3 dibuka — ini bukan aturan gaya, tapi keharusan teknis karena Router & koneksi DB adalah dependency semua controller lain.
- Push langsung ke `main` atau `development` dilarang untuk siapa pun, termasuk Dev 1.

---

## 2. DEV 1 — Core Architect, Security Gates, & Authentication

### Branch: `feature/core-setup` → `feature/auth-login`

### File yang dibuat
```
/config/database.php
/config/constants.php
/core/Router.php
/core/Controller.php
/controllers/AuthController.php
/models/Admin.php
/views/backend/layout.php
/views/frontend/login.php   (atau /views/backend/login.php — putuskan lokasinya di awal)
.htaccess
index.php
```

### Function/Method Rinci

**`/config/database.php`**
```php
function getDbConnection(): PDO
// return koneksi PDO singleton, strict error mode (ATTR_ERRMODE = ERRMODE_EXCEPTION)
```

**`/core/Router.php`** (class `Router`)
```php
addRoute(string $method, string $path, callable|array $handler): void
dispatch(string $requestUri, string $requestMethod): void
```

**`/core/Controller.php`** (base class, di-extend semua controller)
```php
protected function render(string $view, array $data = []): void
protected function requireLogin(): void   // cek $_SESSION, redirect ke login kalau kosong
protected function jsonResponse(array $data, int $statusCode = 200): void
```

**`/controllers/AuthController.php`**
```php
showLoginForm(): void
login(): void          // ambil $_POST, verifikasi lewat Admin::findByUsername() + password_verify()
logout(): void          // session_destroy(), redirect ke login
```

**`/models/Admin.php`**
```php
findByUsername(string $username): array|false
```

### Checklist keamanan wajib
- [ ] Session regenerate ID setelah login sukses (cegah session fixation)
- [ ] Semua halaman backend include guard `requireLogin()` di awal
- [ ] Password dicek pakai `password_verify()`, tidak pernah dibandingkan string langsung

---

## 3. DEV 2 — Content Delivery (Berita + Konten Halaman Statis)

> Digabung dari News + `pages_content` yang sebelumnya dipegang Dev 3 — temanya konsisten: "manajemen konten teks yang di-edit admin".

### Branch: `feature/news-crud` → `feature/pages-content`

### File yang dibuat
```
/controllers/NewsController.php
/controllers/PageController.php
/models/News.php
/models/Page.php
/views/frontend/home.php
/views/frontend/news-detail.php
/views/backend/news-list.php
/views/backend/news-form.php
/views/backend/pages-edit.php
```

### Function/Method Rinci

**`/models/News.php`**
```php
getAll(int $limit = null): array
getBySlug(string $slug): array|false
create(array $data): int          // return insert id
update(int $id, array $data): bool
delete(int $id): bool
```

**`/controllers/NewsController.php`**
```php
index(): void              // publik — landing page, grid berita
show(string $slug): void   // publik — detail satu berita
listAdmin(): void          // backend — tabel list berita
createForm(): void
store(): void               // handle POST, panggil generateSlug() + validateUpload()
editForm(int $id): void
updateNews(int $id): void
destroy(int $id): void

// helper privat
private generateSlug(string $title): string
private validateUpload(array $file): bool   // cek MIME type JPEG/PNG, ukuran, generate nama unik
```

**`/models/Page.php`**
```php
getByIdentifier(string $identifier): array|false
updateContent(string $identifier, string $htmlContent, int $adminId): bool
```

**`/controllers/PageController.php`**
```php
show(string $identifier): void      // publik — render halaman SOP
editForm(string $identifier): void   // backend — form dengan rich text editor
save(string $identifier): void
```

### Checklist wajib
- [ ] `htmlspecialchars()` di semua output teks dari `news.title`, kecuali `content` yang memang html dari rich text editor (butuh sanitasi berbeda, bukan `htmlspecialchars` mentah — ini perlu didiskusikan, lihat catatan di bagian bawah)
- [ ] Slug di-generate otomatis, tapi cek duplikat sebelum insert (tambahkan suffix angka kalau slug sudah ada)
- [ ] Validasi upload thumbnail: whitelist MIME type, bukan cuma cek ekstensi file

---

## 4. DEV 3 — Interactive Search, Document Auditor, & Security Layer

> Ditambah CSV import (sebelumnya belum ada pemiliknya) dan cleanup `rate_limit_attempts` (masih perlu didiskusikan mekanismenya).

### Branch: `feature/advisor-search` → `feature/csv-import` → `feature/rate-limiting` → `feature/downloadable-files`

### File yang dibuat
```
/controllers/AdvisorController.php
/models/Advisor.php
/controllers/FileController.php
/models/DownloadableFile.php
/config/security.php
/views/frontend/search-dosen.php
/views/frontend/jadwal.php
/assets/js/search-dosen.js
/views/backend/advisor-import.php
/views/backend/files-manage.php
```

### Function/Method Rinci

**`/models/Advisor.php`**
```php
findByNimAndName(string $nim, string $studentName): array   // return array of rows, bukan single row (lihat catatan multi-advisor)
truncateAndReload(array $rows): bool   // dipanggil setelah validasi CSV lolos
```

**`/controllers/AdvisorController.php`**
```php
searchForm(): void            // publik — render form pencarian
search(): void                  // publik — endpoint AJAX, POST /controllers/search_advisor.php
                                  //   1. cek rate limit dulu (panggil security.php)
                                  //   2. normalisasi input (trim, lowercase, kompres spasi)
                                  //   3. panggil Advisor::findByNimAndName()
                                  //   4. return JSON — pesan error SERAGAM baik NIM salah, nama salah, atau keduanya
importCsvForm(): void           // backend
importCsv(): void                // backend — validasi penuh dulu sebelum truncateAndReload()

// helper privat
private normalizeInput(string $value): string
private validateCsvStructure(array $rows): array   // return ['valid' => bool, 'errors' => []]
```

**`/config/security.php`**
```php
generateCsrfToken(): string
verifyCsrfToken(string $token): bool
checkRateLimit(string $ipAddress, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool
recordAttempt(string $ipAddress, string $endpoint): void
cleanupOldAttempts(int $olderThanSeconds = 3600): void   // dipanggil inline tiap request masuk — lihat catatan
```

**`/models/DownloadableFile.php`**
```php
getActiveByCategory(string $category): array|false
upload(string $category, string $fileName, string $filePath, int $adminId): int
archiveOld(string $category): bool   // set is_active = 0 untuk file lama di kategori sama
```

**`/controllers/FileController.php`**
```php
listAdmin(): void
uploadForm(): void
store(): void          // panggil DownloadableFile::archiveOld() dulu, baru upload() baru
delete(int $id): void
```

**`/assets/js/search-dosen.js`**
```javascript
handleSearchSubmit(event)     // preventDefault, ambil nilai form
fetchAdvisorData(nim, studentName)   // fetch() ke endpoint, return Promise
renderResults(data)             // tampilkan hasil / pesan error generik di DOM
```

### Checklist keamanan wajib
- [ ] Query pencarian pakai Prepared Statement dengan `AND`, bukan `LIKE`
- [ ] Pesan error dari `search()` **sama persis** terlepas dari NIM salah/nama salah/keduanya salah — jangan bocorkan info lewat teks error
- [ ] CSV import: validasi struktur kolom + tipe data SEBELUM `TRUNCATE`, supaya kalau file rusak, data lama tidak ikut hilang
- [ ] Rate limit cleanup: **belum diputuskan** apakah dipanggil inline tiap request atau lewat cron/event scheduler terpisah — perlu dibahas di evaluasi Jumat sebelum diimplementasi, jangan diasumsikan salah satu

---

## 5. Titik Keputusan yang Masih Terbuka

Ini bukan hal yang bisa saya putuskan sepihak karena menyentuh keputusan desain yang lebih luas — perlu dibahas bertiga atau diangkat ke evaluasi Jumat:

1. **Format response `search()` saat satu NIM+Nama punya >1 jenis pembimbing** (Wali, PI, TA sekaligus). Saya sudah tulis `findByNimAndName()` return array supaya siap untuk kasus ini, tapi frontend (`renderResults()`) perlu tahu cara menampilkan lebih dari satu hasil.
2. **Sanitasi HTML dari rich text editor** — `htmlspecialchars()` akan merusak tag HTML yang memang sengaja disimpan dari TinyMCE/CKEditor. Perlu library sanitasi berbeda (mis. HTML Purifier) atau strategi lain; ini belum ditentukan.
3. **Mekanisme cleanup `rate_limit_attempts`** — cron job, inline per-request, atau MySQL Event Scheduler.

Ketiganya sebaiknya diputuskan sebelum masing-masing dev mulai coding bagian terkait, supaya tidak perlu refactor besar di tengah jalan.
