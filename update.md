# UPDATE — Dev 3 Work Plan

> Laporan analisa mendalam & rencana pengerjaan untuk bagian Dev 3.
> Dibuat: 22 Juli 2026 | Terakhir diperbarui: 22 Juli 2026

---

## Daftar Tanggung Jawab Dev 3

| # | Area | File Utama |
|---|------|-----------|
| 1 | Pencarian Dosen (AJAX) | `controllers/AdvisorController.php`, `models/Advisor.php`, `views/frontend/search-dosen.php`, `assets/js/search-dosen.js` |
| 2 | Import CSV | `controllers/AdvisorController.php`, `models/Advisor.php`, `views/backend/advisor-import.php` |
| 3 | Rate Limiting | `config/security.php`, `schema_polinest_baak.sql` |
| 4 | Manajemen File | `controllers/FileController.php`, `models/DownloadableFile.php`, `views/backend/files-manage.php` |
| 5 | Keamanan | `config/security.php` |

---

## SESI 1 — Critical Fixes

> Harus dikerjakan paling awal karena menghambat fitur lain atau menyebabkan crash.

### 1.1 JSON Decode Crash pada Input Invalid

**File:** `controllers/AdvisorController.php:39-40`

**Masalah:** Jika body request bukan JSON valid, `json_decode()` mengembalikan `null`. Baris 42 (`$payload['nim']`) akan crash dengan `TypeError: Cannot access offset of type string on null` di PHP 8.x.

**Rencana Perbaikan:**

```php
// controllers/AdvisorController.php — method search()
// Ganti baris 39-43:

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    $this->sendJson(400, [
        'status' => 'error',
        'message' => 'Format data tidak valid.'
    ]);
}
```

---

### 1.2 Error Handling Import Menggunakan `die()` — HTTP Status 200

**File:** `controllers/AdvisorController.php:112-141`

**Masalah:** Semua error case di `processImport()` menggunakan `die()` yang mengembalikan HTTP 200 dengan plain text. Tidak ada logging. User melihat halaman error jelek tanpa status code yang benar.

**Rencana Perbaikan:**

Ganti semua `die()` di `processImport()` dengan redirect kembali ke form + flash message via session:

```php
// Contoh untuk setiap error case:
$_SESSION['import_error'] = 'Pesan error spesifik';
header("Location: " . BASE_URL . "admin/import-csv");
exit;
```

Dan di `views/backend/advisor-import.php`, tampilkan flash message:

```php
<?php if (isset($_SESSION['import_error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($_SESSION['import_error']); ?>
    </div>
    <?php unset($_SESSION['import_error']); ?>
<?php endif; ?>
```

**File yang perlu diubah:**
- `controllers/AdvisorController.php` — baris 112, 116, 120, 125, 131, 141, 154, 165, 171, 186-194
- `views/backend/advisor-import.php` — tambah flash message block

---

### 1.3 Frontend View Tanpa HTML Wrapper — Bootstrap CSS Tidak Load

**File:** `views/frontend/search-dosen.php`

**Masalah:** View ini tidak punya `<!DOCTYPE html>`, `<head>`, atau `<link>` ke Bootstrap CSS. Saat di-render dengan `$useLayout = false` (default), browser menerima fragment HTML tanpa styling. **Semua class Bootstrap (`container`, `row`, `card`, `btn`, `form-control`, dll) tidak berfungsi.**

**Rencana Perbaikan:**

**Opsi A (Recommended):** Buat frontend layout wrapper baru di `views/frontend/layout.php`:

```php
// views/frontend/layout.php (BARU)
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
            <a class="navbar-brand" href="<?= BASE_URL ?>">BAAK Politeknik Nest</a>
        </div>
    </nav>

    <?= $content ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

**Opsi B (Quicker):** Buat file view standalone dengan HTML wrapper langsung di `search-dosen.php` (seperti yang dilakukan `login.php`). Tapi ini tidak DRY.

**Perubahan di Controller:**

```php
// controllers/AdvisorController.php — method showSearchPage()
// Tambah parameter ketiga: useLayout = true
$this->render('frontend/search-dosen', [
    'pageTitle' => 'Pencarian Dosen Pembimbing',
    'csrf_token' => generateCsrfToken(),
], true);
```

**Catatan:** `core/Controller.php` method `render()` perlu dimodifikasi untuk support multiple layout (frontend vs backend), atau buat metode `renderFrontend()` terpisah.

---

### 1.4 Search Icon Hilang Permanen Setelah Submit Form

**File:** `assets/js/search-dosen.js:37-39, 69-72`

**Masalah:** `submitBtn.textContent = 'Mencari...'` menghapus semua child nodes termasuk `<i class="bi bi-search">`. Saat restore di `.finally()`, icon tidak kembali karena `textContent` hanya mengembalikan teks biasa.

**Rencana Perbaikan:**

```javascript
// assets/js/search-dosen.js

// Simpan innerHTML asli saat pertama kali
const txtAsli = submitBtn.innerHTML;

// Saat loading (ganti baris 37-39):
submitBtn.disabled = true;
submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mencari...';

// Saat restore di .finally() (ganti baris 69-72):
.finally(() => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = txtAsli;
})
```

---

## SESI 2 — Performance & Data Integrity

> Perbaikan yang meningkatkan kecepatan dan keandalan.

### 2.1 `LOWER()` SQL Meniadakan Composite Index — Search O(n)

**File:** `models/Advisor.php:39`, `schema_polinest_baak.sql:174`

**Masalah:** Query `WHERE LOWER(nim) = :nim AND LOWER(student_name) = :student_name` membuat index `idx_nim_student_name (nim, student_name)` **tidak bisa dipakai**. MySQL harus full table scan.

**Rencana Perbaikan:**

**Langkah 1:** Simpan data dalam format lowercase saat CSV import:

```php
// models/Advisor.php — method truncateAndReload()
// Di dalam loop INSERT (sekitar baris 82), normalisasi sebelum insert:
foreach ($rows as $row) {
    $normalizedNim = mb_strtolower(trim($row['nim']), 'UTF-8');
    $normalizedName = mb_strtolower(trim($row['student_name']), 'UTF-8');
    $stmtInsert->execute([
        ':nim' => $normalizedNim,
        ':student_name' => $normalizedName,
        ':advisor_name' => trim($row['advisor_name']),
        ':advisor_type' => trim($row['advisor_type']),
    ]);
}
```

**Langkah 2:** Hapus `LOWER()` dari query search:

```php
// models/Advisor.php — method findByNimAndName()
// Ganti baris 37-40:
$normalizedNim = $this->normalize($nim);    // sudah mb_strtolower
$normalizedName = $this->normalize($studentName); // sudah mb_strtolower

$sql = "SELECT advisor_name, advisor_type 
        FROM student_advisors 
        WHERE nim = :nim AND student_name = :student_name
        ORDER BY FIELD(advisor_type, 'Wali', 'Magang', 'TA') ASC";
```

**Langkah 3:** Setelah deploy, jalankan query untuk convert data existing:

```sql
UPDATE student_advisors SET 
    nim = LOWER(nim), 
    student_name = LOWER(student_name);
```

---

### 2.2 Rate Limiting — Tabel Tumbuh Tanpa Batas

**File:** `config/security.php:125`, `schema_polinest_baak.sql:104-110`

**Masalah:** Setiap request INSERT baris baru (`attempt_count` selalu = 1). Column `attempt_count` tidak pernah > 1. Tabel tumbuh sangat cepat. Cleanup hanya 10% probabilistik.

**Rencana Perbaikan:**

**Langkah 1:** Ubah schema tabel `rate_limit_attempts`:

```sql
-- Drop tabel lama
DROP TABLE IF EXISTS `rate_limit_attempts`;

-- Buat tabel baru dengan design yang lebih baik
CREATE TABLE `rate_limit_attempts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `endpoint` varchar(255) NOT NULL,
    `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
    `attempt_count` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ip_endpoint_window` (`ip_address`, `endpoint`, `window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Langkah 2:** Rewrite `checkRateLimit()` di `config/security.php`:

```php
function checkRateLimit(string $ip, string $endpoint, int $maxAttempts, int $windowSeconds): bool {
    global $db;
    
    if ($db === null) {
        return false; // Fail-closed, bukan fail-open
    }
    
    $windowStart = time() - $windowSeconds;
    
    try {
        // Bersihkan record lama (hanya untuk IP+endpoint ini)
        $stmtCleanup = $db->prepare(
            "DELETE FROM rate_limit_attempts 
             WHERE ip_address = :ip AND endpoint = :endpoint AND window_start < :window_start"
        );
        $stmtCleanup->execute([
            ':ip' => $ip,
            ':endpoint' => $endpoint,
            ':window_start' => date('Y-m-d H:i:s', $windowStart),
        ]);
        
        // Hitung attempt dalam window
        $stmtCount = $db->prepare(
            "SELECT COALESCE(SUM(attempt_count), 0) as total 
             FROM rate_limit_attempts 
             WHERE ip_address = :ip AND endpoint = :endpoint 
             AND window_start >= :window_start"
        );
        $stmtCount->execute([
            ':ip' => $ip,
            ':endpoint' => $endpoint,
            ':window_start' => date('Y-m-d H:i:s', $windowStart),
        ]);
        $result = $stmtCount->fetch();
        $currentAttempts = (int)$result['total'];
        
        if ($currentAttempts >= $maxAttempts) {
            return false;
        }
        
        // Insert atau update (atomic)
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
        
    } catch (Exception $e) {
        logError("Rate limit error: " . $e->getMessage());
        return false; // Fail-closed
    }
}
```

**Langkah 3:** Tambah index untuk window_start (opsional, untuk cleanup lebih cepat):

```sql
ALTER TABLE rate_limit_attempts ADD INDEX idx_window_start (window_start);
```

---

### 2.3 Tidak Ada Duplikasi Detection di CSV Import

**File:** `controllers/AdvisorController.php:174-179`, `schema_polinest_baak.sql`

**Masalah:** CSV dengan baris duplikat (NIM + advisor_type sama tapi advisor_name beda) akan diinsert semua. Schema tidak punya `UNIQUE(nim, advisor_type)`.

**Rencana Perbaikan:**

**Langkah 1:** Deduplicate di PHP sebelum insert:

```php
// controllers/AdvisorController.php — method processImport()
// Setelah validasi baris, sebelum truncateAndReload:

// Deduplicate: per NIM+advisor_type, ambil yang terakhir
$uniqueRows = [];
foreach ($rows as $row) {
    $key = strtolower(trim($row['nim'])) . '|' . trim($row['advisor_type']);
    $uniqueRows[$key] = $row; // overwrite = ambil yang terakhir
}
$rows = array_values($uniqueRows);
```

**Langkah 2:** (Opsional) Tambah UNIQUE constraint di schema:

```sql
ALTER TABLE student_advisors ADD UNIQUE KEY uk_nim_type (nim, advisor_type);
```

---

### 2.4 `fgetcsv` Max Length Terlalu Kecil

**File:** `controllers/AdvisorController.php:148, 159`

**Masalah:** `fgetcsv($handle, 1000, ",")` membatasi max line length 1000 karakter. Nama dosen Indonesia panjang bisa terpotong.

**Rencana Perbaikan:**

```php
// Ganti baris 148 dan 159:
$header = fgetcsv($handle, -1, ","); // -1 = unlimited
// ...
$data = fgetcsv($handle, -1, ",");
```

---

### 2.5 CSV Header Comparison Tidak Handle BOM/Casing

**File:** `controllers/AdvisorController.php:152`

**Masalah:** CSV dari Excel/Google Sheets bisa punya UTF-8 BOM (`\xEF\xBB\xBF`) atau casing beda (`NIM` vs `nim`). Strict `!==` gagal.

**Rencana Perbaikan:**

```php
// Setelah fgetcsv header (baris 148), sebelum comparison:
// Strip UTF-8 BOM jika ada
if (count($header) > 0) {
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
}
// Normalize casing dan trim whitespace
$header = array_map(function($h) {
    return strtolower(trim($h));
}, $header);
```

---

## SESI 3 — Security Hardening

> Peningkatan keamanan aplikasi.

### 3.1 CSRF Token Tidak Pernah Diregenerasi

**File:** `config/security.php:38-43`

**Masalah:** Token CSRF dibuat sekali per session. Jika bocor (XSS, browser extension, Referer header), valid selama session hidup.

**Rencana Perbaikan:**

```php
// config/security.php

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi baru: regenerate setelah form submission
function regenerateCsrfToken(): void {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Panggil regenerate setelah setiap form submission berhasil:
// Di AdvisorController::processImport() setelah import sukses:
regenerateCsrfToken();
// Di FileController::store() setelah upload sukses:
regenerateCsrfToken();
// Di FileController::delete() setelah delete sukses:
regenerateCsrfToken();
```

---

### 3.2 CSP `'unsafe-inline'` Lemahkan Proteksi XSS

**File:** `config/security.php:27`

**Masalah:** `script-src 'self' 'unsafe-inline'` memungkinkan semua inline `<script>` berjalan, termasuk yang diinjeksi attacker.

**Rencana Perbaikan:**

Gunakan nonce-based CSP:

```php
// config/security.php — tambah fungsi baru:
function generateCspNonce(): string {
    if (empty($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csp_nonce'];
}

// Modifikasi emit_security_headers():
function emit_security_headers(): void {
    $nonce = generateCspNonce();
    
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdn.tiny.cloud; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
           "img-src 'self' data:; " .
           "font-src 'self' https://cdn.jsdelivr.net; " .
           "connect-src 'self'; " .
           "frame-ancestors 'self';");
    // ... other headers unchanged
}

// Di semua view yang pakai inline script, tambahkan nonce:
// <script nonce="<?= generateCspNonce() ?>">
```

**File yang perlu diupdate:**
- `config/security.php` — tambah fungsi + modifikasi CSP header
- `views/frontend/search-dosen.php:62` — tambah nonce pada inline script
- `views/backend/*.php` — semua view dengan inline script/onsubmit

---

### 3.3 Rate Limiting Fail-Open — DB Down = No Limit

**File:** `config/security.php:88, 136`

**Masalah:** Jika database error, rate limiting **dibypass**. Attacker bisa exploit saat DB down.

**Rencana Perbaikan:**

Ganti `return true; // Fail-open` menjadi `return false; // Fail-closed` di kedua lokasi:

```php
// Line 88 — di checkRateLimit():
return false; // Fail-closed: reject jika infrastruktur security down

// Line 136 — di catch block:
return false; // Fail-closed on DB error
```

---

### 3.4 `BASE_URL` Unescaped di Inline JavaScript — Host Header XSS

**File:** `views/frontend/search-dosen.php:63`

**Masalah:** `BASE_URL` dari `$_SERVER['HTTP_HOST']` di-echo tanpa escaping di dalam string JS literal. Jika attacker kontrol Host header, bisa inject JS.

**Rencana Perbaikan:**

```php
// views/frontend/search-dosen.php — baris 62-64:
<script nonce="<?= generateCspNonce() ?>">
    const BASE_URL = <?= json_encode(BASE_URL) ?>;
</script>
```

`json_encode()` akan output JSON string yang sudah proper escaped (termasuk quotes dan special characters).

---

### 3.5 HSTS Header Dikirim di HTTP

**File:** `config/security.php:30`

**Masalah:** HSTS di koneksi HTTP bisa memaksa browser upgrade selamanya ke HTTPS, bahkan jika server tidak punya SSL.

**Rencana Perbaikan:**

```php
// Hanya kirim HSTS saat HTTPS:
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
```

---

### 3.6 No Max-Row Limit untuk CSV Import (DoS Vector)

**File:** `controllers/AdvisorController.php`

**Masalah:** Admin bisa upload CSV dengan jutaan baris, menyebabkan memory exhaustion dan transaksi DB panjang.

**Rencana Perbaikan:**

```php
// controllers/AdvisorController.php — di processImport(), setelah loop reading:
$maxRows = 50000; // Batas wajar untuk data mahasiswa
if (count($rows) > $maxRows) {
    fclose($handle);
    $_SESSION['import_error'] = "File CSV melebihi batas maksimum {$maxRows} baris.";
    header("Location: " . BASE_URL . "admin/import-csv");
    exit;
}
```

---

## SESI 4 — Robustness & Error Handling

> Membuat aplikasi lebih tangguh terhadap edge cases.

### 4.1 Race Condition Saat CSV Import — Search Return Empty

**File:** `models/Advisor.php:73-89`

**Masalah:** Antara `DELETE` (baris 73) dan selesainya semua `INSERT` (baris 82-88), concurrent search request mendapat hasil kosong.

**Rencana Perbaikan (Staging Table Approach):**

```php
// models/Advisor.php — method truncateAndReload() rewrite:

public function truncateAndReload(array $rows): void {
    if (empty($rows)) {
        throw new Exception("Data CSV kosong, proses impor dibatalkan.");
    }
    
    $this->db->beginTransaction();
    
    try {
        // 1. Buat tabel staging sementara
        $this->db->exec("DROP TEMPORARY TABLE IF EXISTS tmp_student_advisors");
        $this->db->exec("
            CREATE TEMPORARY TABLE tmp_student_advisors 
            LIKE student_advisors
        ");
        
        // 2. Insert ke staging
        $sqlInsert = "INSERT INTO tmp_student_advisors (nim, student_name, advisor_name, advisor_type) 
                      VALUES (:nim, :student_name, :advisor_name, :advisor_type)";
        $stmtInsert = $this->db->prepare($sqlInsert);
        
        foreach ($rows as $row) {
            $stmtInsert->execute([
                ':nim' => trim($row['nim']),
                ':student_name' => trim($row['student_name']),
                ':advisor_name' => trim($row['advisor_name']),
                ':advisor_type' => trim($row['advisor_type']),
            ]);
        }
        
        // 3. Atomic swap
        $this->db->exec("DROP TABLE student_advisors");
        $this->db->exec("RENAME TABLE tmp_student_advisors TO student_advisors");
        
        $this->db->commit();
        
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

**Catatan:** `TEMPORARY TABLE` dan `RENAME` hanya berfungsi di MySQL/MariaDB. Pastikan user MySQL punya permission untuk `DROP TABLE` dan `RENAME TABLE`.

**Alternatif simpler (jika staging table tidak memungkinkan):** Tambah flag di tabel `student_advisors` atau tabel terpisah untuk menandai status import:

```php
// Set flag "importing" sebelum mulai
$this->db->exec("INSERT INTO import_status (status) VALUES ('importing')");

// ... lakukan DELETE + INSERT ...

// Hapus flag setelah selesai
$this->db->exec("DELETE FROM import_status WHERE status = 'importing'");
```

Dan di search, cek flag import sebelum query:
```php
// Di AdvisorController::search():
$status = $this->db->query("SELECT status FROM import_status LIMIT 1")->fetch();
if ($status) {
    $this->sendJson(503, [
        'status' => 'error',
        'message' => 'Data sedang diperbarui, silakan coba beberapa saat lagi.'
    ]);
}
```

---

### 4.2 Schema SQL FK Gagal Execute — Index Hilang

**File:** `schema_polinest_baak.sql:230`

**Masalah:** Foreign key `fk_news_admin` membutuhkan index di `news.created_by`, tapi tidak didefinisikan di bagian indexes.

**Rencana Perbaikan:**

```sql
-- Tambahkan di bagian indexes news table:
KEY `idx_news_created_by` (`created_by`),

-- Pastikan urutan CREATE TABLE: indexes dulu baru constraints
-- atau gunakan ALTER TABLE:
ALTER TABLE `news` ADD INDEX `idx_news_created_by` (`created_by`);
```

---

### 4.3 Race Condition Upload File — Duplikat Aktif

**File:** `models/DownloadableFile.php:30-31`

**Masalah:** Dua admin upload file kategori sama bersamaan → dua record `is_active = 1` untuk kategori sama.

**Rencana Perbaikan:**

```php
// models/DownloadableFile.php — method replaceByCategory():
public function replaceByCategory(string $category, string $fileName, string $filePath, int $adminId): bool {
    $this->db->beginTransaction();
    
    try {
        // Lock baris yang akan di-update
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
        throw $e; // Tangkap Throwable, bukan hanya Exception
    }
}
```

---

### 4.4 FileController `catch (Exception)` vs `Throwable`

**File:** `controllers/FileController.php:92`

**Masalah:** `replaceByCategory()` bisa melempar `Throwable` (termasuk `Error`, `TypeError`). `catch (Exception)` tidak menangkapnya, file physical orphan.

**Rencana Perbaikan:**

```php
// controllers/FileController.php — method store():
// Ganti baris 92:
} catch (Throwable $e) {  // Bukan Exception
    unlink($destination);
    logError("File upload DB error: " . $e->getMessage());
    $_SESSION['import_error'] = 'Gagal menyimpan data ke database.';
    header("Location: " . BASE_URL . "admin/files");
    exit;
}
```

---

### 4.5 `$_SESSION['admin_id'] ?? 1` Fallback Berbahaya

**File:** `controllers/FileController.php:85`

**Masalah:** Jika session hilang, file diatribusikan ke admin id=1 (super admin).

**Rencana Perbaikan:**

```php
// Hapus fallback. requireLogin() sudah dipanggil di baris 38.
// Jika session hilang setelah requireLogin(), itu bug fatal.
$adminId = $_SESSION['admin_id']; // Tanpa fallback
```

---

### 4.6 Soft Delete Tidak Bersihkan File Physical

**File:** `controllers/FileController.php:104-120`

**Masalah:** File lama hanya di-deactivate di DB, file physical tetap di disk.

**Rencana Perbaikan:**

Tambahkan mekanisme cleanup periodik:

```php
// controllers/FileController.php — tambah method baru:
private function cleanupOrphanedFiles(): void {
    $uploadsDir = self::UPLOAD_DIR;
    $model = new DownloadableFile();
    
    // Ambil semua file_path aktif
    $activeFiles = $model->getActiveFileNames(); // perlu method baru di model
    $activePaths = array_column($activeFiles, 'file_path');
    
    // Scan directory
    $files = glob($uploadsDir . 'doc_*');
    foreach ($files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $activePaths)) {
            // File orphan > 7 hari? Hapus
            if (filemtime($file) < time() - (7 * 24 * 3600)) {
                unlink($file);
                logInfo("Cleaned orphaned file: {$filename}");
            }
        }
    }
}
```

Panggil method ini secara periodik (misalnya 1x sehari via cron atau saat admin login).

---

### 4.7 No Fetch Timeout — Button Disabled Selamanya

**File:** `assets/js/search-dosen.js:42`

**Masalah:** Jika server hang, button "Mencari..." tidak pernah restore.

**Rencana Perbaikan:**

```javascript
// assets/js/search-dosen.js — method search
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 detik timeout

fetch(BASE_URL + 'api/advisors/search', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ nim: nimValue, student_name: namaValue }),
    signal: controller.signal
})
.then(async response => {
    clearTimeout(timeoutId);
    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.message || 'Terjadi kesalahan sistem.');
    }
    return data;
})
// ... rest unchanged
.catch(error => {
    if (error.name === 'AbortError') {
        tampilkanError('Permintaan timeout. Silakan coba lagi.');
    } else {
        tampilkanError(error.message || 'Gagal terhubung ke server.');
    }
})
.finally(() => {
    clearTimeout(timeoutId);
    submitBtn.disabled = false;
    submitBtn.innerHTML = txtAsli;
});
```

---

### 4.8 No Empty State untuk Hasil Kosong

**File:** `assets/js/search-dosen.js:60-64`

**Masalah:** Jika `data` array kosong, tabel muncul tanpa baris data.

**Rencana Perbaikan:**

```javascript
// assets/js/search-dosen.js — di .then() handler
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

---

### 4.9 Tidak Ada Audit Logging untuk File Operations

**File:** `controllers/FileController.php`

**Rencana Perbaikan:**

```php
// Di method store() setelah upload berhasil:
logInfo("File uploaded: {$originalName} (category: {$category}, admin_id: {$adminId})");

// Di method delete() setelah deactivate berhasil:
logInfo("File deactivated: id={$id} (admin_id: {$_SESSION['admin_id']})");
```

---

### 4.10 `$_GET['status']` Tidak Ditampilkan di `files-manage.php`

**File:** `views/backend/files-manage.php`

**Rencana Perbaikan:**

```php
<!-- Tambahkan di awan file, setelah opening comment -->
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

---

## SESI 5 — Polish & Cleanup

> Perbaikan kualitas kode, aksesibilitas, dan UX.

### 5.1 CSRF Token Inconsistency

**File:** `views/backend/files-manage.php:12,71` vs `views/backend/advisor-import.php:21`

**Masalah:** `files-manage.php` pakai `e($csrf_token)`, `advisor-import.php` pakai `htmlspecialchars($csrf_token ?? '')`.

**Rencana Perbaikan:** Standarisasi ke `e()` di semua view:

```php
// advisor-import.php baris 21 — ganti:
<input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
```

---

### 5.2 `SELECT *` di Model — Fragile

**File:** `models/DownloadableFile.php:18`

**Rencana Perbaikan:**

```php
// Ganti SELECT * dengan explicit columns:
$stmt = $this->db->query(
    "SELECT id, file_category, file_name, file_path, uploaded_at 
     FROM downloadable_files 
     WHERE is_active = 1 
     ORDER BY uploaded_at DESC"
);
```

---

### 5.3 Label `<label>` Tanpa `for` — Aksesibilitas

**File:** `views/frontend/search-dosen.php:23-28`, `views/backend/files-manage.php:15-26`

**Rencana Perbaikan:**

```php
// search-dosen.php — NIM input
<label for="input-nim" class="form-label fw-bold">NIM</label>
<input type="text" class="form-control form-control-lg" id="input-nim" ...>

// search-dosen.php — Nama input  
<label for="input-nama" class="form-label fw-bold">Nama Lengkap</label>
<input type="text" class="form-control form-control-lg" id="input-nama" ...>

// files-manage.php — Kategori select
<label for="file_category" class="form-label fw-bold">Kategori Dokumen</label>
<select name="file_category" id="file_category" class="form-select" ...>

// files-manage.php — File input
<label for="document_file" class="form-label fw-bold">Pilih File</label>
<input type="file" name="document_file" id="document_file" ...>
```

---

### 5.4 Backend Layout Tidak Load Bootstrap Icons CSS

**File:** `views/backend/layout.php:6`

**Rencana Perbaikan:**

```php
<!-- Tambahkan di <head> setelah Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
```

---

### 5.5 `jadwal.php` Dead Links dan Tidak Ada Route

**File:** `views/frontend/jadwal.php:10,18`, `index.php`

**Rencana Perbaikan:**

1. Tambah route di `index.php`:
```php
$router->addRoute('GET', '/jadwal', function() {
    $controller = new HomeController();
    $controller->showJadwal(); // method baru di HomeController
});
```

2. Di `views/frontend/jadwal.php`, ganti `href="#"` dengan link dinamis atau hardcode yang benar.

3. Buat method `showJadwal()` di `HomeController` yang fetch data dari `DownloadableFile` model atau data statis.

---

### 5.6 `strtotime()` Bisa Return False

**File:** `views/backend/files-manage.php:62`

**Rencana Perbaikan:**

```php
<td>
    <?php 
    $timestamp = strtotime($file['uploaded_at']);
    echo e($timestamp ? date('d M Y H:i', $timestamp) : $file['uploaded_at']);
    ?>
</td>
```

---

### 5.7 Tidak Ada Loading Indicator untuk Upload/Import

**Files:** `views/backend/files-manage.php`, `views/backend/advisor-import.php`

**Rencana Perbaikan:**

```html
<!-- Tambahkan di kedua form submit button -->
<button type="submit" class="btn btn-success w-100" 
        onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Memproses...'; this.form.submit();">
    Simpan File
</button>
```

---

### 5.8 No ARIA Live Region untuk Dynamic Content

**File:** `views/frontend/search-dosen.php:37`

**Rencana Perbaikan:**

```html
<div id="container-hasil-pencarian" class="mt-4 d-none" aria-live="polite">
```

---

### 5.9 No Backup/Export Sebelum CSV Truncate

**File:** `models/Advisor.php:73`

**Rencana Perbaikan (Opsional):**

```php
// Di truncateAndReload(), sebelum DELETE:
$backupQuery = "SELECT * FROM student_advisors INTO OUTFILE '/tmp/advisors_backup_" . time() . ".csv' 
                FIELDS TERMINATED BY ',' ENCLOSED BY '\"' 
                LINES TERMINATED BY '\n'";
$this->db->exec($backupQuery);
```

**Atau backup ke application storage:**
```php
$backupPath = __DIR__ . '/../storage/backups/';
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}
$backupFile = $backupPath . 'advisors_backup_' . date('Y-m-d_H-i-s') . '.csv';
// ... export to file
```

---

### 5.10 HTMLPurifier Path Tidak Dicek Existensinya

**File:** `config/security.php:149`

**Rencana Perbaikan:**

```php
function sanitizeHtmlContent(string $html): string {
    $purifierPath = __DIR__ . '/../libs/htmlpurifier-4.15.0-standalone/HTMLPurifier.standalone.php';
    
    if (!file_exists($purifierPath)) {
        logError("HTMLPurifier library not found at: {$purifierPath}");
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
    
    require_once $purifierPath;
    // ... rest unchanged
}
```

---

## Fitur yang Belum Dikerjakan (PRD Gaps)

| # | Fitur | Referensi | Estimasi |
|---|-------|-----------|----------|
| A | Admin UI untuk view file history (old/deleted files) | PRD §10B | Medium |
| B | Fasilitas restore file lama | PRD §10B | Medium |
| C | NIM format validation | Technical Design Open Item #5 | Small |
| D | Test cases untuk normalisasi input | Technical Design Open Item #6 | Small |
| E | Frontend layout wrapper (untuk semua frontend views) | - | Medium |
| F | Periodic cleanup orphaned files | - | Small |
| G | Import history/log (siapa import kapan) | - | Small |
| H | Preview CSV sebelum import (destructive) | - | Medium |

---

## File yang Perlu Diubah (Checklist)

### Core
- [ ] `config/security.php` — CSRF regeneration, CSP nonce, fail-closed rate limit, HSTS conditional
- [ ] `controllers/AdvisorController.php` — JSON validation, error handling, BOM strip, max rows
- [ ] `controllers/FileController.php` — Throwable catch, remove admin_id fallback, audit logging
- [ ] `models/Advisor.php` — Hapus LOWER() dari query, normalisasi di insert, staging table import
- [ ] `models/DownloadableFile.php` — Explicit columns, FOR UPDATE lock, Throwable
- [ ] `schema_polinest_baak.sql` — Tambah index `news.created_by`, rate limit redesign, UNIQUE constraint

### Views
- [ ] `views/frontend/search-dosen.php` — HTML wrapper, label `for`, nonce, BASE_URL escaping, aria-live
- [ ] `views/frontend/layout.php` (BARU) — Frontend layout wrapper
- [ ] `views/backend/files-manage.php` — Flash messages, label `for`, date validation
- [ ] `views/backend/advisor-import.php` — Flash message error, CSRF consistency, role="alert"
- [ ] `views/backend/layout.php` — Tambah Bootstrap Icons CSS link

### JavaScript
- [ ] `assets/js/search-dosen.js` — Icon restore, fetch timeout, empty state handling

### Route
- [ ] `index.php` — Tambah route untuk `/jadwal`

---

## Referensi Dokumentasi

- **PRD:** `PRD.md` — §5 (Pencarian Dosen), §10B (Backend Management), §11 (Keamanan)
- **Technical Design:** `PRD_Technical_Design_Modul_Pencarian_Dosen.md` — Open items di baris 71-80
- **Database Schema:** `schema_polinest_baak.sql`
- **Flow Chart:** `Alur Sistem (Flow Chart Teknis).txt`
- **Pembagian Tugas:** `Pembagian_Tugas_Branch_GitHub.md`

---

## Estimasi Waktu Pengerjaan

| Sesi | Estimasi | Prioritas |
|------|----------|-----------|
| SESI 1 — Critical Fixes | 3-4 jam | Wajib |
| SESI 2 — Performance | 2-3 jam | Wajib |
| SESI 3 — Security | 3-4 jam | Wajib |
| SESI 4 — Robustness | 4-5 jam | Tinggi |
| SESI 5 — Polish | 2-3 jam | Medium |
| **Total** | **14-19 jam** | - |
