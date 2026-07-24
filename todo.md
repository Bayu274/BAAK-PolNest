# TODO — BAAK-PolNest Dev 3 Work Plan

> Checklist perbaikan lengkap untuk bagian Dev 3.
> Dibuat: 22 Juli 2026 | Terakhir diperbarui: 24 Juli 2026 — Semua Phase Selesai ✓

---

## Arsitektur Proyek (Referensi Cepat)

```
BAAK-PolNest/
├── index.php                    (Router bootstrap, 99 baris)
├── config/
│   ├── constants.php            (BASE_URL, BASE_PATH, APP_ENV)
│   ├── database.php             (PDO singleton via getDbConnection())
│   ├── logger.php               (logError, logInfo, logWarning)
│   └── security.php             (CSRF, rate limit, CSP, headers, e())
├── core/
│   ├── Controller.php           (Base class: render(), requireLogin(), jsonResponse())
│   └── Router.php               (Custom router: addRoute(), dispatch())
├── controllers/
│   ├── AdvisorController.php    (Search + CSV import, 196 baris)
│   ├── FileController.php       (Upload/download file, 121 baris)
│   ├── HomeController.php       (Halaman publik, 21 baris)
│   ├── AuthController.php       (Login/logout, 64 baris)
│   ├── NewsController.php       (CRUD berita, 234 baris)
│   ├── PageController.php       (CRUD halaman, 154 baris)
│   └── DashboardController.php  (Dashboard admin, 10 baris)
├── models/
│   ├── Advisor.php              (Query + import dosen, 102 baris)
│   ├── DownloadableFile.php     (CRUD file, 60 baris)
│   ├── Admin.php                (Find by username, 13 baris)
│   ├── News.php                 (CRUD berita, 64 baris)
│   └── Page.php                 (CRUD halaman, 58 baris)
├── views/
│   ├── frontend/
│   │   ├── home.php             (Fragment, tanpa layout wrapper)
│   │   ├── login.php            (Standalone HTML, punya DOCTYPE sendiri)
│   │   ├── search-dosen.php     (Fragment, tanpa layout wrapper)
│   │   ├── news-detail.php      (Fragment)
│   │   ├── page-detail.php      (Fragment)
│   │   └── jadwal.php           (Fragment, DEAD LINKS, tanpa route)
│   └── backend/
│       ├── layout.php           (Admin sidebar layout, $content injection)
│       ├── advisor-import.php   (CSV upload form)
│       ├── files-manage.php     (File management)
│       ├── news-list.php
│       ├── news-form.php
│       ├── pages-list.php
│       ├── pages-create.php
│       └── pages-edit.php
├── assets/js/
│   └── search-dosen.js          (AJAX search, 115 baris)
├── schema_polinest_baak.sql     (DB schema, 241 baris)
└── storage/uploads/             (File upload directory)
```

### Pola Rendering (UPDATE SETELAH PHASE 1)

- `Controller::render($view, $data, $useLayout)`:
  - `$useLayout = false` → render fragment langsung (frontend views)
  - `$useLayout = true` → wrap dengan `views/backend/layout.php` (backend admin)
  - `$useLayout = 'frontend'` → wrap dengan `views/frontend/layout.php` (**READY**, belum ada view yang pakai)

### Pola Error Handling Saat Ini

- **Backend admin**: Banyak pakai `die()` yang mengembalikan HTTP 200 plain text
- **API (search)**: Pakai `$this->sendJson()` dengan status code benar
- **Frontend**: Tidak ada flash message untuk error

### Pola Keamanan Saat Ini (UPDATE SETELAH PHASE 1)

- CSRF: Token dibuat sekali per session, **regenerateCsrfToken() tersedia** (akan dipanggil di Phase 2)
- CSP: **Nonce-based** untuk `script-src`, `'unsafe-inline'` sudah dihapus
- Rate Limit: **Fail-closed** jika DB error, **atomic upsert** dengan `ON DUPLICATE KEY UPDATE`
- HSTS: **Hanya dikirim saat HTTPS** connection

---

## Depedensi Antar-Fix

```
Phase 1 (Foundation) ──> Phase 2 (Core Logic) ──> Phase 3 (UI/Views) ──> Phase 4 (Polish)
     │                       │                        │
     ├─ security.php fixes   ├─ schema changes        ├─ layout wrapper
     ├─ Controller.php mod   ├─ Advisor model         ├─ view flash msgs
     └─ logger available     └─ AdvisorController     └─ JS fixes
```

**Phase 1 WAJIB duluan** karena security.php dipanggil semua file lain.
**Phase 2 setelah Phase 1** karena model/controller depends on config.
**Phase 3 setelah Phase 2** karena view depends on controller.
**Phase 4 terakhir** sebagai finishing.

---

## PHASE 1 — Foundation & Security

> Perubahan di file config/core yang jadi dependensi semua phase berikutnya.
> Estimasi: 3-4 jam

---

### 1A. `config/security.php` — CSRF Token Regeneration

- [x] **Tambah fungsi `regenerateCsrfToken()`**

  **Lokasi:** `config/security.php`, tambah setelah fungsi `generateCsrfToken()` (setelah baris 43)

  **Kode yang ditambahkan:**
  ```php
  if (!function_exists('regenerateCsrfToken')) {
      function regenerateCsrfToken(): void {
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
  }
  ```

  **Mekanisme:** Membuat token baru dan menimpa `$_SESSION['csrf_token']` yang lama. Token lama langsung tidak valid.

  **Dipanggil di:**
  - `controllers/AdvisorController.php` method `processImport()` — setelah import sukses, sebelum redirect
  - `controllers/FileController.php` method `store()` — setelah upload sukses, sebelum redirect
  - `controllers/FileController.php` method `delete()` — setelah deactivate sukses, sebelum redirect

  **Alasan:** Jika CSRF token bocor (XSS, browser extension, Referer header), token hanya valid untuk satu kali penggunaan form. Setelah form submit berhasil, token lama dibuang dan diganti baru.

---

### 1B. `config/security.php` — CSP Nonce-Based

- [x] **Tambah fungsi `generateCspNonce()`**

  **Lokasi:** `config/security.php`, tambah sebelum fungsi `emit_security_headers()`

  **Kode yang ditambahkan:**
  ```php
  if (!function_exists('generateCspNonce')) {
      function generateCspNonce(): string {
          if (empty($_SESSION['csp_nonce'])) {
              $_SESSION['csp_nonce'] = bin2hex(random_bytes(16));
          }
          return $_SESSION['csp_nonce'];
      }
  }
  ```

- [x] **Ubah CSP header di `emit_security_headers()`**

  **Lokasi:** `config/security.php`, baris 27

  **Sebelum:**
  ```php
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
  ```

  **Sesudah:**
  ```php
  $nonce = generateCspNonce();
  header("Content-Security-Policy: default-src 'self'; " .
         "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net; " .
         "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
         "img-src 'self' data: blob:; " .
         "font-src 'self' https://cdn.jsdelivr.net data:; " .
         "connect-src 'self'; frame-ancestors 'self'; " .
         "base-uri 'self'; form-action 'self'");
  ```

  **Catatan:** `style-src` tetap `'unsafe-inline'` karena Bootstrap membutuhkan inline styles. Hanya `script-src` yang diubah dari `'unsafe-inline'` ke `'nonce-...'`.

  **Dampak yang harus dicek:** Semua `<script>` di view files harus ditambahkan attribute `nonce="<?= generateCspNonce() ?>"`. Ini dilakukan di Phase 3.

---

### 1C. `config/security.php` — HSTS Conditional

- [x] **Bungkus HSTS header dengan cek HTTPS**

  **Lokasi:** `config/security.php`, baris 30

  **Sebelum:**
  ```php
  header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
  ```

  **Sesudah:**
  ```php
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
  }
  ```

  **Alasan:** HSTS di koneksi HTTP memaksa browser upgrade ke HTTPS selamanya, bahkan jika server tidak punya SSL. Ini bisa membuat situs tidak bisa diakses.

---

### 1D. `config/security.php` — Rate Limit Fail-Closed

- [x] **Ubah kedua lokasi fail-open menjadi fail-closed**

  **Lokasi 1:** `config/security.php`, baris 88 (di dalam blok catch koneksi DB)
  ```php
  // Sebelum:
  return true; // Fail-open sementara
  // Sesudah:
  return false; // Fail-closed: reject jika infrastruktur security down
  ```

  **Lokasi 2:** `config/security.php`, baris 136 (di dalam blok catch PDOException)
  ```php
  // Sebelum:
  return true; // Fail-open
  // Sesudah:
  return false; // Fail-closed on DB error
  ```

  **Alasan:** Jika DB error, attacker bisa exploit tanpa batasan rate limit. Fail-closed lebih aman — reject request daripada bypass security.

---

### 1E. `config/security.php` — Rate Limit Rewrite (Atomic Upsert)

- [x] **Rewrite seluruh fungsi `checkRateLimit()`**

  **Lokasi:** `config/security.php`, baris 88-141

  **Masalah saat ini:**
  - Setiap request INSERT baris baru dengan `attempt_count = 1` (baris 125)
  - Column `attempt_count` tidak pernah > 1 — sia-sia
  - Tabel tumbuh sangat cepat tanpa batas
  - Cleanup hanya 10% probabilitik (stochastic)

  **Kode baru (ganti seluruh function):**
  ```php
  if (!function_exists('checkRateLimit')) {
      function checkRateLimit(string $ip, string $endpoint, int $maxAttempts = 10, int $windowSeconds = 60): bool {
          if (!function_exists('getDbConnection')) {
              require_once __DIR__ . '/database.php';
          }
          
          $db = null;
          try {
              $db = getDbConnection(); 
          } catch (Exception $e) {
              logError("Security config gagal memanggil koneksi: " . $e->getMessage());
              return false; // Fail-closed
          }

          $windowStart = time() - $windowSeconds;
          
          try {
              // Bersihkan record lama (khusus IP+endpoint ini)
              $stmtCleanup = $db->prepare(
                  "DELETE FROM rate_limit_attempts 
                   WHERE ip_address = :ip AND endpoint = :endpoint AND window_start < :ws"
              );
              $stmtCleanup->execute([
                  ':ip' => $ip,
                  ':endpoint' => $endpoint,
                  ':ws' => date('Y-m-d H:i:s', $windowStart),
              ]);
              
              // Hitung attempt dalam window
              $stmtCount = $db->prepare(
                  "SELECT COALESCE(SUM(attempt_count), 0) as total 
                   FROM rate_limit_attempts 
                   WHERE ip_address = :ip AND endpoint = :endpoint 
                   AND window_start >= :ws"
              );
              $stmtCount->execute([
                  ':ip' => $ip,
                  ':endpoint' => $endpoint,
                  ':ws' => date('Y-m-d H:i:s', $windowStart),
              ]);
              $row = $stmtCount->fetch(PDO::FETCH_ASSOC);
              $currentAttempts = (int)($row['total'] ?? 0);
              
              if ($currentAttempts >= $maxAttempts) {
                  return false; // Ditolak
              }
              
              // Atomic upsert — INSERT atau increment
              $stmtInsert = $db->prepare(
                  "INSERT INTO rate_limit_attempts (ip_address, endpoint, window_start, attempt_count)
                   VALUES (:ip, :endpoint, NOW(), 1)
                   ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1"
              );
              $stmtInsert->execute([
                  ':ip' => $ip,
                  ':endpoint' => $endpoint,
              ]);
              
              return true;
              
          } catch (PDOException $e) {
              logError("DB Rate Limit Error: " . $e->getMessage());
              return false; // Fail-closed
          }
      }
  }
  ```

  **Catatan:** Fungsi `cleanupOldAttempts()` bisa dihapus karena cleanup sekarang dilakukan per-IP+endpoint di dalam fungsi ini.

---

### 1F. `config/security.php` — HTMLPurifier Path Check

- [x] **Tambah pengecekan `file_exists()` sebelum `require_once`**

  **Lokasi:** `config/security.php`, baris 158 (di dalam fungsi `sanitizeHtmlContent()`)

  **Sebelum:**
  ```php
  require_once __DIR__ . '/../libs/htmlpurifier-4.15.0-standalone/HTMLPurifier.standalone.php';
  ```

  **Sesudah:**
  ```php
  $purifierPath = __DIR__ . '/../libs/htmlpurifier-4.15.0-standalone/HTMLPurifier.standalone.php';
  if (!file_exists($purifierPath)) {
      logError("HTMLPurifier library not found at: {$purifierPath}");
      return htmlspecialchars($dirty, ENT_QUOTES, 'UTF-8');
  }
  require_once $purifierPath;
  ```

  **Alasan:** Jika library hilang, `require_once` akan throw fatal error. Dengan fallback ke `htmlspecialchars()`, aplikasi tetap berjalan meski tanpa HTMLPurifier.

---

### 1G. `core/Controller.php` — Multi-Layout Support

- [x] **Modifikasi method `render()` untuk support frontend layout**

  **Lokasi:** `core/Controller.php`, method `render()` (baris 5-36)

  **Sebelum:**
  ```php
  protected function render(string $view, array $data = [], bool $useLayout = false): void
  {
      // ...
      if ($useLayout) {
          ob_start();
          require $viewPath;
          $content = ob_get_clean();
          $layoutPath = __DIR__ . '/../views/backend/layout.php';
          require $layoutPath;
          return;
      }
      require $viewPath;
  }
  ```

  **Sesudah:**
  ```php
  protected function render(string $view, array $data = [], bool|string $useLayout = false): void
  {
      if (session_status() === PHP_SESSION_NONE) {
          session_start();
      }
      generateCsrfToken();
      extract($data);
      $viewPath = __DIR__ . '/../views/' . $view . '.php';

      if (!file_exists($viewPath)) {
          logError("View not found: {$view}");
          if (getenv('APP_ENV') === 'production') {
              die('Terjadi kesalahan sistem. Silakan coba lagi nanti.');
          } else {
              die("View tidak ditemukan: {$view}");
          }
      }

      if ($useLayout === true) {
          ob_start();
          require $viewPath;
          $content = ob_get_clean();
          $layoutPath = __DIR__ . '/../views/backend/layout.php';
          require $layoutPath;
          return;
      }

      if ($useLayout === 'frontend') {
          ob_start();
          require $viewPath;
          $content = ob_get_clean();
          $layoutPath = __DIR__ . '/../views/frontend/layout.php';
          require $layoutPath;
          return;
      }

      require $viewPath;
  }
  ```

  **Opsi yang didukung:**
  - `false` (default) → render fragment tanpa layout (backward compatible, semua frontend views saat ini)
  - `true` → wrap dengan backend admin layout (sudah ada)
  - `'frontend'` → wrap dengan frontend public layout (akan dibuat di Phase 3)

  **Backward compatibility:** Semua panggilan `$this->render()` yang ada saat ini tidak terpengaruh karena default-nya `false`.

---

### 1H. `schema_polinest_baak.sql` — Rate Limit Table Redesign

- [x] **Drop dan buat ulang tabel `rate_limit_attempts`**

  **Lokasi:** `schema_polinest_baak.sql`, baris 104-110

  **Sebelum:**
  ```sql
  CREATE TABLE `rate_limit_attempts` (
    `id` int(11) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `endpoint` varchar(100) NOT NULL,
    `attempt_count` int(11) NOT NULL DEFAULT 1,
    `window_start` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ```

  **Sesudah:**
  ```sql
  DROP TABLE IF EXISTS `rate_limit_attempts`;
  CREATE TABLE `rate_limit_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `endpoint` varchar(255) NOT NULL,
    `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
    `attempt_count` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ip_endpoint_window` (`ip_address`, `endpoint`, `window_start`),
    KEY `idx_window_start` (`window_start`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ```

  **Perubahan:**
  - `endpoint` dari `varchar(100)` → `varchar(255)` (lebih fleksibel)
  - Tambah `AUTO_INCREMENT` di `id`
  - Tambah `UNIQUE KEY` pada `(ip_address, endpoint, window_start)` — kunci atomic upsert
  - Tambah `KEY idx_window_start` untuk cleanup lebih cepat

  **Migration untuk DB existing:**
  ```sql
  -- CAUTION: Hapus semua data rate limit yang ada
  DROP TABLE IF EXISTS `rate_limit_attempts`;
  -- Jalankan CREATE TABLE di atas
  ```

---

### 1I. `schema_polinest_baak.sql` — UNIQUE Constraint student_advisors

- [x] **Tambah UNIQUE KEY `(nim, advisor_type)`**

  **Lokasi:** `schema_polinest_baak.sql`, bagian indexes untuk `student_advisors` (setelah baris 174)

  **Tambahkan:**
  ```sql
  ALTER TABLE `student_advisors`
    ADD UNIQUE KEY `uk_nim_type` (`nim`, `advisor_type`);
  ```

  **Alasan:** Mencegah duplikat data mahasiswa + jenis pembimbing. Jika CSV punya baris duplikat (NIM sama, advisor_type sama tapi advisor_name beda), database akan reject.

  **Catatan:** Jalankan ini SETELAH data existing sudah dinormalisasi (lowercase). Lihat Phase 4 untuk migration query.

---

### 1J. `schema_polinest_baak.sql` — Index news.created_by

- [x] **Tambah index untuk foreign key**

  **Lokasi:** `schema_polinest_baak.sql`, bagian indexes untuk `news` (setelah baris 152)

  **Tambahkan:**
  ```sql
  ALTER TABLE `news`
    ADD INDEX `idx_news_created_by` (`created_by`);
  ```

  **Alasan:** MySQL InnoDB membutuhkan index di column yang jadi referensi foreign key. Tanpa index ini, query JOIN/DELETE yang melibatkan FK `fk_news_admin` akan lambat atau error di beberapa konfigurasi MySQL.

---

## PHASE 2 — Core Logic & Data

> Perubahan di model, controller, dan JS yang merupakan fitur utama.
> Estimasi: 4-5 jam

---

### 2A. `models/Advisor.php` — Hapus LOWER() dari Query

- [x] **Hapus `LOWER()` dari SQL WHERE clause**

  **Lokasi:** `models/Advisor.php`, baris 37-40 (method `findByNimAndName()`)

  **Sebelum:**
  ```php
  $sql = "SELECT advisor_name, advisor_type 
          FROM student_advisors 
          WHERE LOWER(nim) = :nim AND LOWER(student_name) = :student_name
          ORDER BY FIELD(advisor_type, 'Wali', 'Magang', 'TA') ASC";
  ```

  **Sesudah:**
  ```php
  $sql = "SELECT advisor_name, advisor_type 
          FROM student_advisors 
          WHERE nim = :nim AND student_name = :student_name
          ORDER BY FIELD(advisor_type, 'Wali', 'Magang', 'TA') ASC";
  ```

  **Alasan:** `LOWER()` di SQL memaksa MySQL melakukan full table scan karena index `idx_nim_student_name (nim, student_name)` tidak bisa dipakai dengan fungsi. Jika data sudah disimpan dalam format lowercase (di-insert normal), `LOWER()` tidak diperlukan.

  **Prasyarat:** Fix 2B (normalisasi di insert) harus dijalankan terlebih dahulu, DAN data existing harus sudah di-lowercase (migration di Phase 4).

---

### 2B. `models/Advisor.php` — Normalisasi Data saat Insert

- [x] **Tambah `mb_strtolower()` saat insert data CSV**

  **Lokasi:** `models/Advisor.php`, method `truncateAndReload()`, di dalam loop INSERT (baris 76-88)

  **Sebelum:**
  ```php
  foreach ($rows as $row) {
      $stmtInsert->execute([
          ':nim'          => $row['nim'],
          ':student_name' => $row['student_name'],
          ':advisor_name' => $row['advisor_name'],
          ':advisor_type' => $row['advisor_type']
      ]);
  }
  ```

  **Sesudah:**
  ```php
  foreach ($rows as $row) {
      $stmtInsert->execute([
          ':nim'          => mb_strtolower(trim($row['nim']), 'UTF-8'),
          ':student_name' => mb_strtolower(trim($row['student_name']), 'UTF-8'),
          ':advisor_name' => trim($row['advisor_name']),
          ':advisor_type' => trim($row['advisor_type']),
      ]);
  }
  ```

  **Alasan:** Menyimpan data dalam format lowercase memungkinkan pencarian tanpa `LOWER()` di SQL, yang membuat index bisa terpakai (Fix 2A).

---

### 2C. `models/Advisor.php` — Staging Table Import (Race Condition Fix)

- [x] **Rewrite `truncateAndReload()` dengan temporary table**

  **Lokasi:** `models/Advisor.php`, method `truncateAndReload()` (baris 63-101)

  **Masalah saat ini:** Antara `DELETE FROM student_advisors` (baris 74) dan selesainya semua INSERT (baris 82-88), concurrent search request mendapat hasil kosong karena tabel kosong.

  **Kode baru (ganti seluruh method):**
  ```php
  public function truncateAndReload(array $rows): void {
      if (empty($rows)) {
          throw new Exception("Data CSV kosong, proses impor dibatalkan.");
      }
      
      $this->db->beginTransaction();
      
      try {
          // 1. Buat temporary table (copy struktur dari student_advisors)
          $this->db->exec("DROP TEMPORARY TABLE IF EXISTS tmp_student_advisors");
          $this->db->exec("CREATE TEMPORARY TABLE tmp_student_advisors LIKE student_advisors");
          
          // 2. Insert data ke staging
          $sqlInsert = "INSERT INTO tmp_student_advisors (nim, student_name, advisor_name, advisor_type) 
                        VALUES (:nim, :student_name, :advisor_name, :advisor_type)";
          $stmtInsert = $this->db->prepare($sqlInsert);
          
          foreach ($rows as $row) {
              $stmtInsert->execute([
                  ':nim'          => mb_strtolower(trim($row['nim']), 'UTF-8'),
                  ':student_name' => mb_strtolower(trim($row['student_name']), 'UTF-8'),
                  ':advisor_name' => trim($row['advisor_name']),
                  ':advisor_type' => trim($row['advisor_type']),
              ]);
          }
          
          // 3. Atomic swap — DROP lama, RENAME staging jadi yang baru
          $this->db->exec("DROP TABLE student_advisors");
          $this->db->exec("RENAME TABLE tmp_student_advisors TO student_advisors");
          
          $this->db->commit();
          
      } catch (Throwable $e) {
          $this->db->rollBack();
          error_log("Gagal Impor CSV: " . $e->getMessage());
          throw new Exception("Terjadi kesalahan sistem saat menyimpan data. Seluruh perubahan telah dibatalkan.");
      }
  }
  ```

  **Mekanisme:**
  1. Buat tabel temporary (copy struktur, tanpa data)
  2. Insert semua data CSV ke temporary table
  3. DROP tabel lama, RENAME temporary jadi nama tabel asli — ini atomic operation
  4. Selama proses, tabel asli masih bisa dibaca (search tetap jalan)

  **Catatan:** `TEMPORARY TABLE` dan `RENAME TABLE` hanya berfungsi di MySQL/MariaDB. Pastikan user MySQL punya permission untuk `DROP TABLE` dan `RENAME TABLE`.

---

### 2D. `models/DownloadableFile.php` — Explicit Columns

- [x] **Ganti `SELECT *` dengan explicit column names**

  **Lokasi:** `models/DownloadableFile.php`, baris 18 (method `getActiveFiles()`)

  **Sebelum:**
  ```php
  $stmt = $this->db->query("SELECT * FROM downloadable_files WHERE is_active = 1 ORDER BY uploaded_at DESC");
  ```

  **Sesudah:**
  ```php
  $stmt = $this->db->query(
      "SELECT id, file_category, file_name, file_path, uploaded_at 
       FROM downloadable_files 
       WHERE is_active = 1 
       ORDER BY uploaded_at DESC"
  );
  ```

  **Alasan:** `SELECT *` fragile — jika schema berubah (column ditambah/dihapus), query bisa break atau mengekspos data yang tidak seharusnya. Explicit columns juga lebih jelas intent-nya.

---

### 2E. `models/DownloadableFile.php` — FOR UPDATE Lock

- [x] **Tambah `SELECT ... FOR UPDATE` sebelum update di `replaceByCategory()`**

  **Lokasi:** `models/DownloadableFile.php`, method `replaceByCategory()` (baris 24-44)

  **Masalah saat ini:** Dua admin upload file kategori sama bersamaan → keduanya membaca `is_active = 1` rows, keduanya update ke `is_active = 0`, keduanya insert file baru → ada dua record `is_active = 1` untuk kategori sama.

  **Kode baru:**
  ```php
  public function replaceByCategory(string $category, string $fileName, string $filePath, int $adminId): bool {
      $this->db->beginTransaction();
      
      try {
          // Lock existing active rows (block concurrent reads)
          $stmtLock = $this->db->prepare(
              "SELECT id FROM downloadable_files 
               WHERE file_category = :cat AND is_active = 1 
               FOR UPDATE"
          );
          $stmtLock->execute([':cat' => $category]);
          
          // Update (soft-deactivate) baris lama
          $stmtArchive = $this->db->prepare(
              "UPDATE downloadable_files SET is_active = 0 
               WHERE file_category = :cat AND is_active = 1"
          );
          $stmtArchive->execute([':cat' => $category]);
          
          // Insert file baru
          $stmtInsert = $this->db->prepare(
              "INSERT INTO downloadable_files (file_category, file_name, file_path, uploaded_by, is_active, uploaded_at) 
               VALUES (:cat, :name, :path, :admin_id, 1, NOW())"
          );
          $stmtInsert->execute([
              ':cat' => $category,
              ':name' => $fileName,
              ':path' => $filePath,
              ':admin_id' => $adminId,
          ]);
          
          $this->db->commit();
          return true;
          
      } catch (Throwable $e) {
          $this->db->rollBack();
          throw $e;
      }
  }
  ```

  **Mekanisme `FOR UPDATE`:** Mem-lock baris yang dibaca sehingga transaksi lain harus menunggu sampai transaksi ini commit/rollback. Ini mencegah dua transaksi concurrent membaca data yang sama.

---

### 2F. `models/DownloadableFile.php` — Tambah Method getActiveFileNames()

- [x] **Tambah method untuk mendukung file cleanup**

  **Lokasi:** `models/DownloadableFile.php`, tambah method baru

  **Kode:**
  ```php
  public function getActiveFileNames(): array {
      $stmt = $this->db->query(
          "SELECT file_path FROM downloadable_files WHERE is_active = 1"
      );
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  ```

  **Digunakan oleh:** `FileController::cleanupOrphanedFiles()` di Phase 4.

---

### 2G. `controllers/AdvisorController.php` — JSON Decode Guard

- [x] **Tambah validasi `is_array()` setelah `json_decode()`**

  **Lokasi:** `controllers/AdvisorController.php`, method `search()`, baris 42-43

  **Sebelum:**
  ```php
  $payload = json_decode($rawInput, true);
  
  $nim = isset($payload['nim']) ? trim($payload['nim']) : '';
  $name = isset($payload['student_name']) ? trim($payload['student_name']) : '';
  ```

  **Sesudah:**
  ```php
  $payload = json_decode($rawInput, true);
  
  if (!is_array($payload)) {
      $this->sendJson(400, [
          'status' => 'error',
          'message' => 'Format data tidak valid.'
      ]);
  }
  
  $nim = isset($payload['nim']) ? trim($payload['nim']) : '';
  $name = isset($payload['student_name']) ? trim($payload['student_name']) : '';
  ```

  **Alasan:** `json_decode()` mengembalikan `null` untuk input invalid. Tanpa guard `is_array()`, error message yang dikembalikan ("NIM dan Nama Lengkap wajib diisi") misleading — seharusnya bilang format invalid.

  **Catatan:** `isset()` pada `null` secara teknis tidak crash di PHP 8.x (return `false`), tapi guard eksplisit lebih jelas dan mengembalikan error message yang benar.

---

### 2H. `controllers/AdvisorController.php` — Replace All die() dengan Flash Messages

- [x] **Tambah helper method `importError()`**

  **Lokasi:** `controllers/AdvisorController.php`, tambah method baru (private)

  **Kode:**
  ```php
  private function importError(string $message): void {
      $_SESSION['import_error'] = $message;
      header("Location: " . BASE_URL . "admin/import-csv");
      exit;
  }
  ```

- [x] **Ganti SEMUA `die()` di `processImport()` dengan `$this->importError()`**

  **Lokasi dan perubahan:**

  | Baris | Sebelum | Sesudah |
  |-------|---------|---------|
  | 112 | `die("Error 403: Sesi tidak valid atau kadaluarsa (CSRF Violation).");` | `$this->importError('Sesi tidak valid atau kadaluarsa.');` |
  | 117 | `die("Error: File gagal diunggah atau tidak ditemukan.");` | `$this->importError('File gagal diunggah atau tidak ditemukan.');` |
  | 126 | `die("Error: Ukuran file maksimal 5MB.");` | `$this->importError('Ukuran file maksimal 5MB.');` |
  | 131 | `die("Error: Ekstensi file wajib .csv");` | `$this->importError('Ekstensi file wajib .csv');` |
  | 141 | `die("Error: Tipe file tidak dikenali sebagai format teks CSV yang valid.");` | `$this->importError('Tipe file tidak valid.');` |
  | 154 | `die("Error: Format kolom CSV salah...");` | `$this->importError('Format kolom CSV salah. Harus: nim, student_name, advisor_name, advisor_type');` |
  | 165 | `die("Error: Baris ke-{$rowNumber} memiliki jumlah kolom yang tidak valid.");` | `$this->importError("Baris ke-{$rowNumber}: jumlah kolom tidak valid.");` |
  | 171 | `die("Error: Baris ke-{$rowNumber} memiliki jenis pembimbing yang salah...");` | `$this->importError("Baris ke-{$rowNumber}: jenis pembimbing salah. Hanya: Wali, Magang, TA.");` |
  | 193 | `die($e->getMessage());` | `$this->importError('Gagal menyimpan data ke database.');` |

  **Alasan:** `die()` mengembalikan HTTP 200 dengan plain text, user melihat halaman error jelek, tidak ada logging. Dengan flash messages, user kembali ke form dengan pesan error yang jelas dan ter-styling.

---

### 2I. `controllers/AdvisorController.php` — fgetcsv Unlimited Length

- [x] **Ganti max length `1000` → `-1`**

  **Lokasi:** `controllers/AdvisorController.php`, baris 148 dan 159

  **Sebelum:**
  ```php
  $header = fgetcsv($handle, 1000, ",");     // baris 148
  $data = fgetcsv($handle, 1000, ",");       // baris 159
  ```

  **Sesudah:**
  ```php
  $header = fgetcsv($handle, -1, ",");       // baris 148
  $data = fgetcsv($handle, -1, ",");         // baris 159
  ```

  **Alasan:** Parameter kedua `fgetcsv()` adalah max line length dalam bytes. `1000` bisa memotong nama dosen Indonesia yang panjang. `-1` = unlimited.

---

### 2J. `controllers/AdvisorController.php` — CSV Header BOM/Casing Handling

- [x] **Tambah BOM strip dan casing normalization**

  **Lokasi:** `controllers/AdvisorController.php`, setelah baris 148 (setelah fgetcsv header), sebelum comparison

  **Tambahkan:**
  ```php
  $header = fgetcsv($handle, -1, ",");
  
  // Strip UTF-8 BOM jika ada
  if (count($header) > 0) {
      $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
  }
  
  // Normalize casing dan trim whitespace
  $header = array_map(function($h) {
      return strtolower(trim($h));
  }, $header);
  
  $expectedHeader = ['nim', 'student_name', 'advisor_name', 'advisor_type'];
  if ($header !== $expectedHeader) {
      // ... error handling
  }
  ```

  **Alasan:**
  - CSV dari Excel/Google Sheets sering punya UTF-8 BOM (`\xEF\xBB\xBF`) di awal file
  - User mungkin menulis header dengan casing berbeda (`NIM`, `Nim`, `Student_Name`)
  - Strict `!==` tanpa normalization akan gagal untuk kedua kasus di atas

---

### 2K. `controllers/AdvisorController.php` — CSV Deduplication

- [x] **Tambah deduplication sebelum insert**

  **Lokasi:** `controllers/AdvisorController.php`, setelah loop reading CSV, sebelum `$this->importError()` untuk max rows

  **Tambahkan:**
  ```php
  // Deduplicate: per NIM+advisor_type, ambil yang terakhir
  $uniqueRows = [];
  foreach ($rows as $row) {
      $key = strtolower(trim($row['nim'])) . '|' . trim($row['advisor_type']);
      $uniqueRows[$key] = $row; // overwrite = ambil yang terakhir
  }
  $rows = array_values($uniqueRows);
  ```

  **Alasan:** CSV mungkin punya baris duplikat (NIM sama, advisor_type sama tapi advisor_name beda). Tanpa dedup, semua baris diinsert. Dengan dedup, yang terakhir yang menang.

---

### 2L. `controllers/AdvisorController.php` — Max-Row Limit

- [x] **Tambah batasan jumlah baris CSV**

  **Lokasi:** `controllers/AdvisorController.php`, setelah loop reading CSV (setelah baris ~180), sebelum dedup

  **Tambahkan:**
  ```php
  $maxRows = 50000; // Batas wajar untuk data mahasiswa
  if (count($rows) > $maxRows) {
      fclose($handle);
      $this->importError("File CSV melebihi batas maksimum {$maxRows} baris.");
  }
  ```

  **Alasan:** Admin bisa upload CSV dengan jutaan baris → memory exhaustion + transaksi DB panjang. 50.000 baris cukup untuk data mahasiswa beberapa tahun.

---

### 2M. `controllers/AdvisorController.php` — Regenerate CSRF After Import

- [x] **Panggil `regenerateCsrfToken()` setelah import sukses**

  **Lokasi:** `controllers/AdvisorController.php`, method `processImport()`, baris 188-189 (sebelum redirect)

  **Sebelum:**
  ```php
  $model->truncateAndReload($rows);
  header("Location: " . BASE_URL . "admin/import-csv?status=success");
  exit;
  ```

  **Sesudah:**
  ```php
  $model->truncateAndReload($rows);
  regenerateCsrfToken(); // Regenerate setelah form submission sukses
  header("Location: " . BASE_URL . "admin/import-csv?status=success");
  exit;
  ```

---

### 2N. `controllers/FileController.php` — catch Throwable

- [x] **Ganti `catch (Exception)` dengan `catch (Throwable)`**

  **Lokasi:** `controllers/FileController.php`, baris 92 (method `store()`)

  **Sebelum:**
  ```php
  } catch (Exception $e) {
      unlink($destination);
      die("Error: Gagal menyimpan data ke database.");
  }
  ```

  **Sesudah:**
  ```php
  } catch (Throwable $e) {
      unlink($destination);
      logError("File upload DB error: " . $e->getMessage());
      $_SESSION['import_error'] = 'Gagal menyimpan data ke database.';
      header("Location: " . BASE_URL . "admin/files");
      exit;
  }
  ```

  **Alasan:** `replaceByCategory()` di model bisa melempar `Error`, `TypeError`, atau `PDOException` — semua adalah `Throwable` tapi bukan `Exception`. `catch (Exception)` tidak menangkapnya → file physical orphan di disk.

  **Catatan:** `DownloadableFile.php` sudah benar menggunakan `catch (Throwable)` di model-nya. Tapi controller yang memanggil juga harus konsisten.

---

### 2O. `controllers/FileController.php` — Remove Admin ID Fallback

- [x] **Hapus fallback `?? 1`**

  **Lokasi:** `controllers/FileController.php`, baris 85

  **Sebelum:**
  ```php
  $adminId = $_SESSION['admin_id'] ?? 1;
  ```

  **Sesudah:**
  ```php
  $adminId = $_SESSION['admin_id'];
  ```

  **Alasan:** `requireLogin()` sudah dipanggil di baris 38, yang memastikan `$_SESSION['admin_id']` ada. Jika session hilang setelah `requireLogin()`, itu bug fatal yang harus dilaporkan, bukan difallback ke admin id=1 (super admin). Fallback ini bisa menyebabkan file salah attribution ke super admin.

---

### 2P. `controllers/FileController.php` — Audit Logging + CSRF Regeneration

- [x] **Tambah logging dan CSRF regeneration di `store()`**

  **Lokasi:** `controllers/FileController.php`, method `store()`, setelah `replaceByCategory()` sukses (baris ~95)

  **Tambahkan sebelum redirect:**
  ```php
  regenerateCsrfToken();
  logInfo("File uploaded: {$originalName} (category: {$category}, admin_id: {$adminId})");
  ```

- [x] **Tambah logging dan CSRF regeneration di `delete()`**

  **Lokasi:** `controllers/FileController.php`, method `delete()`, setelah `deactivate()` (baris ~115)

  **Tambahkan sebelum redirect:**
  ```php
  regenerateCsrfToken();
  logInfo("File deactivated: id={$id} (admin_id: {$_SESSION['admin_id']})");
  ```

---

### 2Q. `controllers/FileController.php` — Physical Cleanup Method

- [x] **Tambah method `cleanupOrphanedFiles()`**

  **Lokasi:** `controllers/FileController.php`, tambah method baru (private)

  **Kode:**
  ```php
  private function cleanupOrphanedFiles(): void {
      $uploadsDir = self::UPLOAD_DIR;
      $model = new DownloadableFile();
      
      // Ambil semua file_path yang aktif
      $activeFiles = $model->getActiveFileNames();
      $activePaths = array_column($activeFiles, 'file_path');
      
      // Scan directory upload
      $files = glob($uploadsDir . 'doc_*');
      foreach ($files as $file) {
          $filename = basename($file);
          if (!in_array($filename, $activePaths)) {
              // Hapus file orphan yang lebih dari 7 hari
              if (filemtime($file) < time() - (7 * 24 * 3600)) {
                  unlink($file);
                  logInfo("Cleaned orphaned file: {$filename}");
              }
          }
      }
  }
  ```

  **Penggunaan:** Panggil method ini secara periodik (misalnya 1x sehari via cron, atau saat admin login). Method `getActiveFileNames()` harus ditambahkan di model (Fix 2F).

---

### 2R. `assets/js/search-dosen.js` — Save innerHTML, Restore with Icon

- [x] **Simpan `innerHTML` bukan `textContent`**

  **Lokasi:** `assets/js/search-dosen.js`, baris 38

  **Sebelum:**
  ```javascript
  const txtAsli = submitBtn.textContent;
  ```

  **Sesudah:**
  ```javascript
  const txtAsli = submitBtn.innerHTML;
  ```

- [x] **Gunakan `innerHTML` dengan spinner saat loading**

  **Lokasi:** `assets/js/search-dosen.js`, baris 39

  **Sebelum:**
  ```javascript
  submitBtn.textContent = 'Mencari...';
  ```

  **Sesudah:**
  ```javascript
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mencari...';
  ```

- [x] **Restore `innerHTML` di `.finally()`**

  **Lokasi:** `assets/js/search-dosen.js`, baris 72

  **Sebelum:**
  ```javascript
  submitBtn.textContent = txtAsli;
  ```

  **Sesudah:**
  ```javascript
  submitBtn.innerHTML = txtAsli;
  ```

  **Alasan:** `textContent` menghapus semua child nodes termasuk `<i class="bi bi-search">`. Saat restore, icon tidak kembali karena `textContent` hanya mengembalikan teks biasa.

---

### 2S. `assets/js/search-dosen.js` — Fetch Timeout (AbortController)

- [x] **Tambah AbortController dengan 30 detik timeout**

  **Lokasi:** `assets/js/search-dosen.js`, method `search`, sebelum `fetch()` (baris ~41)

  **Tambahkan sebelum fetch:**
  ```javascript
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 30000);
  ```

  **Tambahkan `signal` ke fetch:**
  ```javascript
  fetch(BASE_URL + 'api/advisors/search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nim: nimValue, student_name: namaValue }),
      signal: controller.signal
  })
  ```

  **Tambahkan `clearTimeout` di `.then()` dan `.finally()`:**
  ```javascript
  .then(async response => {
      clearTimeout(timeoutId);
      // ... rest unchanged
  })
  .finally(() => {
      clearTimeout(timeoutId);
      submitBtn.disabled = false;
      submitBtn.innerHTML = txtAsli;
  });
  ```

  **Ubah `.catch()` untuk handle AbortError:**
  ```javascript
  .catch(error => {
      if (error.name === 'AbortError') {
          tampilkanError('Permintaan timeout. Silakan coba lagi.');
      } else {
          tampilkanError(error.message || 'Gagal terhubung ke server.');
      }
  })
  ```

  **Alasan:** Jika server hang, button "Mencari..." tidak pernah restore. AbortController memastikan fetch dibatalkan setelah 30 detik.

---

### 2T. `assets/js/search-dosen.js` — Empty State Handling

- [x] **Cek array kosong sebelum render tabel**

  **Lokasi:** `assets/js/search-dosen.js`, baris 59-64

  **Sebelum:**
  ```javascript
  .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
          renderTabelHasil(res.data);
      } else {
          tampilkanError('Format data yang diterima tidak sesuai.');
      }
  })
  ```

  **Sesudah:**
  ```javascript
  .then(res => {
      if (res.status === 'success' && Array.isArray(res.data)) {
          if (res.data.length === 0) {
              tampilkanError('Data tidak ditemukan atau kecocokan tidak valid.');
          } else {
              renderTabelHasil(res.data);
          }
      } else {
          tampilkanError('Format data yang diterima tidak sesuai.');
      }
  })
  ```

  **Alasan:** Jika API return `{ status: 'success', data: [] }`, tabel muncul tanpa baris data — user bingung. Sebaiknya tampilkan pesan error yang jelas.

---

## PHASE 3 — Views & Frontend

> Perubahan di view files dan pembuatan frontend layout.
> Estimasi: 4-5 jam

---

### 3A. `views/frontend/layout.php` — FILE BARU

- [x] **Buat frontend layout wrapper**

  **Lokasi:** `views/frontend/layout.php` (file baru)

  **Kode:**
  ```php
  <!DOCTYPE html>
  <html lang="id">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= e($pageTitle ?? 'BAAK Politeknik Nest') ?></title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  </head>
  <body>
      <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
          <div class="container">
              <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">BAAK Politeknik Nest</a>
              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                  <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse" id="navbarNav">
                  <div class="navbar-nav ms-auto">
                      <a class="nav-link" href="<?= BASE_URL ?>">Beranda</a>
                      <a class="nav-link" href="<?= BASE_URL ?>pencarian-dosen">Cari Dosen</a>
                  </div>
              </div>
          </div>
      </nav>

      <?= $content ?>

      <footer class="bg-dark text-white text-center py-3 mt-5">
          <small>&copy; <?= date('Y') ?> BAAK Politeknik Nest</small>
      </footer>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
  </html>
  ```

  **Catatan:** Variabel `$content` diisi oleh `Controller::render()` via `ob_start()` / `ob_get_clean()`. Variabel `$pageTitle` di-pass dari controller.

  **Template ini berfungsi sebagai:**
  - DOCTYPE + HTML head (meta, CSS, icons)
  - Navbar navigasi publik
  - Content area (`$content`)
  - Footer
  - Bootstrap JS bundle

---

### 3B. `views/frontend/search-dosen.php` — Nonce di Script Tags

- [x] **Tambah `nonce` attribute pada semua `<script>`**

  **Lokasi:** `views/frontend/search-dosen.php`, baris 62-65

  **Sebelum:**
  ```php
  <script>
      const BASE_URL = '<?= BASE_URL ?>'; 
  </script>
  <script src="<?= BASE_URL ?>assets/js/search-dosen.js"></script>
  ```

  **Sesudah:**
  ```php
  <script nonce="<?= generateCspNonce() ?>">
      const BASE_URL = <?= json_encode(BASE_URL) ?>;
  </script>
  <script nonce="<?= generateCspNonce() ?>" src="<?= BASE_URL ?>assets/js/search-dosen.js"></script>
  ```

  **Catatan:** Dua perubahan di sini:
  1. Tambah `nonce="<?= generateCspNonce() ?>"` — required oleh CSP fix di Phase 1
  2. `BASE_URL` sekarang di-escape dengan `json_encode()` (Fix 3D)

---

### 3C. `views/frontend/search-dosen.php` — BASE_URL Escaping

- [x] **Escape `BASE_URL` dengan `json_encode()`**

  **Lokasi:** `views/frontend/search-dosen.php`, baris 63

  **Sebelum:**
  ```php
  const BASE_URL = '<?= BASE_URL ?>';
  ```

  **Sesudah:**
  ```php
  const BASE_URL = <?= json_encode(BASE_URL) ?>;
  ```

  **Alasan:** `BASE_URL` berasal dari `$_SERVER['HTTP_HOST']` (config/constants.php baris 4). Jika attacker kontrol Host header, mereka bisa inject JavaScript. `json_encode()` output JSON string yang sudah proper escaped (termasuk quotes dan special characters).

  **Note:** Ini sudah digabung dengan Fix 3B di atas.

---

### 3D. `views/frontend/search-dosen.php` — Label for Attributes

- [x] **Tambah `for` attribute pada label**

  **Lokasi:** `views/frontend/search-dosen.php`, baris 23 dan 27

  **Sebelum:**
  ```php
  <label class="form-label fw-semibold">Nomor Induk Mahasiswa (NIM)</label>
  <!-- ... -->
  <label class="form-label fw-semibold">Nama Lengkap</label>
  ```

  **Sesudah:**
  ```php
  <label for="input-nim" class="form-label fw-semibold">Nomor Induk Mahasiswa (NIM)</label>
  <!-- ... -->
  <label for="input-nama" class="form-label fw-semibold">Nama Lengkap</label>
  ```

  **Alasan:** `for` attribute menghubungkan label dengan input (via `id`). Ini penting untuk aksesibilitas — screen reader bisa memberi tahu user input apa yang sedang di-focus, dan click di label akan focus ke input.

---

### 3E. `views/frontend/search-dosen.php` — ARIA Live Region

- [x] **Tambah `aria-live="polite"` pada container hasil**

  **Lokasi:** `views/frontend/search-dosen.php`, baris 37

  **Sebelum:**
  ```php
  <div id="container-hasil-pencarian" class="mt-4 d-none">
  ```

  **Sesudah:**
  ```php
  <div id="container-hasil-pencarian" class="mt-4 d-none" aria-live="polite">
  ```

  **Alasan:** `aria-live="polite"` memberitahu screen reader bahwa konten di dalamnya akan berubah secara dinamis. Saat hasil pencarian muncul, screen reader akan membacakan isinya.

---

### 3F. `views/frontend/search-dosen.php` — Render dengan Frontend Layout

- [x] **Ubah controller call untuk menggunakan frontend layout**

  **Lokasi:** `controllers/AdvisorController.php`, method `showSearchPage()`

  **Sebelum:**
  ```php
  public function showSearchPage(): void {
      $this->render('frontend/search-dosen', [
          'page_title' => 'Pencarian Dosen Pembimbing'
      ]);
  }
  ```

  **Sesudah:**
  ```php
  public function showSearchPage(): void {
      $this->render('frontend/search-dosen', [
          'pageTitle' => 'Pencarian Dosen Pembimbing',
      ], 'frontend');
  }
  ```

  **Catatan:** Parameter ketiga `'frontend'` akan trigger frontend layout wrapper (Fix 1G). Perhatikan key diubah dari `page_title` ke `pageTitle` untuk konsistensi dengan layout template.

---

### 3G. `views/backend/layout.php` — Bootstrap Icons CSS

- [x] **Tambah Bootstrap Icons CSS link**

  **Lokasi:** `views/backend/layout.php`, setelah baris 6

  **Sebelum:**
  ```html
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  ```

  **Sesudah:**
  ```html
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  ```

  **Dampak:** Semua icon `bi bi-*` di backend views (sidebar, buttons, tables) akan mulai render dengan benar. Tanpa ini, semua icon kosong/tidak terlihat.

---

### 3H. `views/backend/files-manage.php` — Flash Messages

- [x] **Tambah blok flash message di awal file**

  **Lokasi:** `views/backend/files-manage.php`, tambah setelah comment baris 1, sebelum `<div class="container-fluid">`

  **Tambahkan:**
  ```php
  <?php if (isset($_GET['status'])): ?>
      <?php if ($_GET['status'] === 'success'): ?>
          <div class="alert alert-success" role="alert">
              <i class="bi bi-check-circle-fill"></i> File berhasil diunggah!
          </div>
      <?php elseif ($_GET['status'] === 'deleted'): ?>
          <div class="alert alert-info" role="alert">
              <i class="bi bi-trash-fill"></i> File berhasil dihapus.
          </div>
      <?php endif; ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['import_error'])): ?>
      <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i> <?= e($_SESSION['import_error']); ?>
      </div>
      <?php unset($_SESSION['import_error']); ?>
  <?php endif; ?>
  ```

  **Alasan:** Saat ini setelah upload/delete, user redirect ke `?status=success` atau `?status=deleted` tapi tidak ada visual feedback. Flash message memberikan konfirmasi yang jelas.

---

### 3I. `views/backend/files-manage.php` — Label for + ID Attributes

- [x] **Tambah `for` pada label dan `id` pada input/select**

  **Lokasi:** `views/backend/files-manage.php`, baris 15-16 dan 25-26

  **Sebelum:**
  ```php
  <label class="form-label fw-bold">Kategori Dokumen</label>
  <select name="file_category" class="form-select" ...>
  <!-- ... -->
  <label class="form-label fw-bold">Pilih File (PDF / DOCX)</label>
  <input type="file" name="document_file" class="form-control" ...>
  ```

  **Sesudah:**
  ```php
  <label for="file_category" class="form-label fw-bold">Kategori Dokumen</label>
  <select name="file_category" id="file_category" class="form-select" ...>
  <!-- ... -->
  <label for="document_file" class="form-label fw-bold">Pilih File (PDF / DOCX)</label>
  <input type="file" name="document_file" id="document_file" class="form-control" ...>
  ```

---

### 3J. `views/backend/files-manage.php` — strtotime False Check

- [x] **Tambah pengecekan `false` pada `strtotime()`**

  **Lokasi:** `views/backend/files-manage.php`, baris 62

  **Sebelum:**
  ```php
  <td><?php echo e(date('d M Y H:i', strtotime($file['uploaded_at']))); ?></td>
  ```

  **Sesudah:**
  ```php
  <td>
      <?php 
      $timestamp = strtotime($file['uploaded_at']);
      echo e($timestamp ? date('d M Y H:i', $timestamp) : $file['uploaded_at']);
      ?>
  </td>
  ```

  **Alasan:** `strtotime()` bisa return `false` pada input invalid. Pass `false` ke `date()` menghasilkan PHP deprecation warning di PHP 8.1+ dan output yang salah.

---

### 3K. `views/backend/advisor-import.php` — CSRF Consistency

- [x] **Ganti `htmlspecialchars()` dengan `e()`**

  **Lokasi:** `views/backend/advisor-import.php`, baris 21

  **Sebelum:**
  ```php
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
  ```

  **Sesudah:**
  ```php
  <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
  ```

  **Alasan:** `files-manage.php` sudah menggunakan `e()`. Konsistensi dalam codebase penting untuk maintainability. `e()` juga lebih pendek dan sudah include `ENT_QUOTES | ENT_SUBSTITUTE`.

---

### 3L. `views/backend/advisor-import.php` — Flash Message import_error

- [x] **Tambah blok flash message untuk error import**

  **Lokasi:** `views/backend/advisor-import.php`, tambah sebelum form (setelah baris 12)

  **Tambahkan:**
  ```php
  <?php if (isset($_SESSION['import_error'])): ?>
      <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i> <?= e($_SESSION['import_error']); ?>
      </div>
      <?php unset($_SESSION['import_error']); ?>
  <?php endif; ?>
  ```

  **Alasan:** Saat ini semua error import menggunakan `die()` yang tidak menampilkan apapun di form ini. Setelah Fix 2H mengganti `die()` dengan flash messages, view ini perlu menampilkan pesan error tersebut.

---

### 3M. `views/frontend/home.php` — Render dengan Frontend Layout

- [x] **Ubah controller call untuk menggunakan frontend layout**

  **Lokasi:** `controllers/HomeController.php`, method `index()`

  **Sebelum:**
  ```php
  public function index() {
      $latestNews = $this->newsModel->getAll(6);
      $this->render('frontend/home', ['latestNews' => $latestNews]);
  }
  ```

  **Sesudah:**
  ```php
  public function index() {
      $latestNews = $this->newsModel->getAll(6);
      $this->render('frontend/home', ['latestNews' => $latestNews], 'frontend');
  }
  ```

  **Alasan:** `home.php` saat ini adalah fragment tanpa DOCTYPE, head, atau navbar. Dengan frontend layout, halaman beranda akan punya navbar dan footer yang konsisten.

---

### 3N. `index.php` — Route /jadwal

- [x] **Tambah route untuk halaman jadwal**

  **Lokasi:** `index.php`, tambah sebelum route HOME catch-all (baris 92)

  **Tambahkan:**
  ```php
  // --- JADWAL ---
  $router->addRoute('GET', '/jadwal', function() use ($homeController) {
      $homeController->showJadwal();
  });
  ```

- [x] **Tambah method `showJadwal()` di HomeController**

  **Lokasi:** `controllers/HomeController.php`, tambah method baru

  **Kode:**
  ```php
  public function showJadwal() {
      $this->render('frontend/jadwal', ['pageTitle' => 'Jadwal & Pedoman BAAK'], 'frontend');
  }
  ```

  **Alasan:** `jadwal.php` view ada di disk tapi tidak ada route. Link di `home.php` dan navigasi mengarah ke `href="#"` (dead link).

---

### 3O. `views/frontend/jadwal.php` — Fix Dead Links

- [x] **Update href links ke dynamic atau hardcode yang benar**

  **Lokasi:** `views/frontend/jadwal.php`, baris 10 dan 18

  **Sebelum:**
  ```php
  <a href="#" class="list-group-item ...">
  ```

  **Sesudah:** (contoh — perlu disesuaikan dengan URL file download yang sebenarnya)
  ```php
  <a href="<?= BASE_URL ?>admin/files" class="list-group-item ...">
  ```

  **Catatan:** Link ini harusnya mengarah ke file download yang tersedia. Perlu diputuskan apakah jadwal diambil dari tabel `downloadable_files` atau dari sumber lain.

---

## PHASE 4 — Polish & Extras

> Perbaikan kualitas kode, UX, dan finishing.
> Estimasi: 2-3 jam

---

### 4A. `controllers/FileController.php` — Replace die() dengan Flash Messages

- [x] **Tambah helper method `fileError()`**

  **Lokasi:** `controllers/FileController.php`, tambah method baru (private)

  **Kode:**
  ```php
  private function fileError(string $message): void {
      $_SESSION['import_error'] = $message;
      header("Location: " . BASE_URL . "admin/files");
      exit;
  }
  ```

- [x] **Ganti SEMUA `die()` di FileController**

  **Lokasi dan perubahan:**

  | Lokasi | Sebelum | Sesudah |
  |--------|---------|---------|
  | `store()` CSRF | `die("Error 403: CSRF Violation.")` | `$this->fileError('CSRF token tidak valid.')` |
  | `store()` kategori | `die("Error: Kategori file tidak valid.")` | `$this->fileError('Kategori file tidak valid.')` |
  | `store()` upload | `die("Error: File gagal diunggah.")` | `$this->fileError('File gagal diunggah.')` |
  | `store()` ukuran | `die("Error: Ukuran file melebihi batas 10MB.")` | `$this->fileError('Ukuran file maksimal 10MB.')` |
  | `store()` ekstensi | `die("Error: Hanya file PDF dan DOCX...")` | `$this->fileError('Hanya file PDF dan DOCX yang diizinkan.')` |
  | `store()` MIME | `die("Error: Konten file tidak cocok...")` | `$this->fileError('Konten file tidak valid.')` |
  | `store()` move | `die("Error: Gagal memindahkan file...")` | `$this->fileError('Gagal memindahkan file ke server.')` |
  | `delete()` CSRF | `die("Error 403: CSRF Violation.")` | `$this->fileError('CSRF token tidak valid.')` |

---

### 4B. Loading Indicator di Form Buttons

- [x] **Tambah loading state pada submit buttons**

  **Lokasi:**
  - `views/backend/files-manage.php`, tombol submit upload
  - `views/backend/advisor-import.php`, tombol submit import

  **Contoh untuk files-manage.php:**
  ```html
  <button type="submit" class="btn btn-success w-100" 
          onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Memproses...'; this.form.submit();">
      Simpan File
  </button>
  ```

  **Contoh untuk advisor-import.php:**
  ```html
  <button type="submit" class="btn btn-primary w-100" 
          onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Memproses...'; this.form.submit();">
      Import Data
  </button>
  ```

  **Alasan:** Upload/import bisa memakan waktu. Tanpa loading indicator, user mungkin klik berkali-kali atau bingung apakah proses sudah jalan.

---

### 4C. SQL Migration untuk Data Existing

- [x] **Buat migration query untuk lowercase data**

  **Catatan:** Jalankan SETELAH semua code fix di-deploy dan schema sudah di-update.

  ```sql
  -- Migration: Normalisasi data student_advisors ke lowercase
  -- Jalankan SEKALI setelah deploy
  
  UPDATE student_advisors SET 
      nim = LOWER(nim), 
      student_name = LOWER(student_name);
  
  -- Verifikasi
  SELECT COUNT(*) as total FROM student_advisors;
  SELECT nim, student_name FROM student_advisors WHERE nim != LOWER(nim) OR student_name != LOWER(student_name);
  -- Harus return 0 baris
  ```

  **Kapan jalankan:** Setelah Fix 2B (normalisasi di insert) dan Fix 2A (hapus LOWER dari query) sudah di-deploy. Data lama harus di-lowercase agar pencarian tetap works.

---

### 4D. Backup Mechanism Sebelum CSV Truncate (Opsional)

- [x] **Tambah backup ke file sebelum truncateAndReload()**

  **Lokasi:** `models/Advisor.php`, method `truncateAndReload()`, sebelum DELETE

  **Kode (opsional):**
  ```php
  // Backup existing data
  $backupPath = __DIR__ . '/../storage/backups/';
  if (!is_dir($backupPath)) {
      mkdir($backupPath, 0755, true);
  }
  $backupFile = $backupPath . 'advisors_backup_' . date('Y-m-d_H-i-s') . '.csv';
  
  $existingData = $this->db->query("SELECT * FROM student_advisors")->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($existingData)) {
      $handle = fopen($backupFile, 'w');
      fputcsv($handle, ['nim', 'student_name', 'advisor_name', 'advisor_type']);
      foreach ($existingData as $row) {
          fputcsv($handle, $row);
      }
      fclose($handle);
      logInfo("Backup created: {$backupFile}");
  }
  ```

  **Alasan:** Jika import CSV salah (data tidak lengkap, format salah), tidak ada cara mengembalikan data lama. Backup memungkinkan rollback manual.

---

## File yang Perlu Diubah (Checklist Lengkap)

### Core & Config
- [x] `config/security.php` — CSRF regeneration, CSP nonce, fail-closed rate limit, HSTS conditional, HTMLPurifier check, rate limit rewrite
- [x] `core/Controller.php` — Multi-layout support (frontend + backend)
- [x] `schema_polinest_baak.sql` — Rate limit redesign, UNIQUE constraint, news index

### Models
- [x] `models/Advisor.php` — Hapus LOWER(), normalisasi di insert, staging table import, backup mechanism
- [x] `models/DownloadableFile.php` — Explicit columns, FOR UPDATE lock, getActiveFileNames()

### Controllers
- [x] `controllers/AdvisorController.php` — JSON guard, die() replacement, fgetcsv unlimited, BOM/casing, dedup, max-row, CSRF regen
- [x] `controllers/FileController.php` — Throwable catch, remove fallback, audit logging, CSRF regen, cleanup method, die() replacement (all die() → fileError)
- [x] `controllers/HomeController.php` — showJadwal() method, frontend layout

### Views
- [x] `views/frontend/layout.php` (BARU) — Frontend layout wrapper
- [x] `views/frontend/search-dosen.php` — HTML wrapper via layout, nonce, BASE_URL escaping, label for, aria-live
- [x] `views/frontend/home.php` — Frontend layout render
- [x] `views/frontend/jadwal.php` — Fix dead links
- [x] `views/backend/layout.php` — Bootstrap Icons CSS, loading indicator JS
- [x] `views/backend/files-manage.php` — Flash messages, label for, strtotime check, loading indicator
- [x] `views/backend/advisor-import.php` — CSRF consistency, flash message, loading indicator

### JavaScript
- [x] `assets/js/search-dosen.js` — innerHTML save/restore, fetch timeout, empty state

### Routes
- [x] `index.php` — Route /jadwal

### Migrations
- [x] `migrations/001_lower_existing_data.sql` (BARU) — Lowercase existing data di student_advisors

---

## Referensi Dokumentasi

- **PRD:** `PRD.md` — §5 (Pencarian Dosen), §10B (Backend Management), §11 (Keamanan)
- **Technical Design:** `PRD_Technical_Design_Modul_Pencarian_Dosen.md` — Open items
- **Database Schema:** `schema_polinest_baak.sql`
- **Flow Chart:** `Alur Sistem (Flow Chart Teknis).txt`
- **Pembagian Tugas:** `Pembagian_Tugas_Branch_GitHub.md`

---

## Estimasi Waktu Pengerjaan

| Phase | Estimasi | Prioritas | Dependencies |
|-------|----------|-----------|--------------|
| **Phase 1** — Foundation & Security | 3-4 jam | Wajib | Tidak ada |
| **Phase 2** — Core Logic & Data | 4-5 jam | Wajib | Phase 1 |
| **Phase 3** — Views & Frontend | 4-5 jam | Wajib | Phase 1 + 2 |
| **Phase 4** — Polish & Extras | 2-3 jam | Medium | Phase 1 + 2 + 3 |
| **Total** | **13-17 jam** | - | - |
