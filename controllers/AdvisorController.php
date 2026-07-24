<?php
/**
 * BAAK-PolNest - Advisor Controller
 * Branch: feature/advisor-search
 */

// Muat stub keamanan
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../models/Advisor.php';

class AdvisorController extends Controller {

    /**
     * Menampilkan halaman pencarian publik (GET /pencarian-dosen)
     */
    public function showSearchPage(): void {
        $this->render('frontend/search-dosen', [
            'pageTitle' => 'Pencarian Dosen Pembimbing',
        ], 'frontend');
    }

    /**
     * Memproses pencarian data via AJAX Fetch API (POST /api/advisors/search)
     */
    public function search(): void {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (!checkRateLimit($ipAddress, 'search_advisor', 10, 60)) {
            $this->sendJson(429, [
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'
            ]);
        }

        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        // [2G] Guard: pastikan payload adalah array
        if (!is_array($payload)) {
            $this->sendJson(400, [
                'status' => 'error',
                'message' => 'Format data tidak valid.'
            ]);
        }

        $nim = isset($payload['nim']) ? trim($payload['nim']) : '';
        $name = isset($payload['student_name']) ? trim($payload['student_name']) : '';

        if ($nim === '' || $name === '') {
            $this->sendJson(400, [
                'status' => 'error',
                'message' => 'NIM dan Nama Lengkap wajib diisi.'
            ]);
        }

        $model = new Advisor();
        $results = $model->findByNimAndName($nim, $name);

        if (empty($results)) {
            $this->sendJson(404, [
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau kecocokan tidak valid.'
            ]);
        }

        $this->sendJson(200, [
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Standarisasi pengiriman JSON response yang bersih dan aman
     */
    private function sendJson(int $statusCode, array $data): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Menampilkan antarmuka Halaman Impor CSV di Backend
     */
    public function importCsvForm(): void {
        $this->requireLogin();

        $this->render('backend/advisor-import', [
            'page_title' => 'Impor Data Dosen Pembimbing',
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    /**
     * [2H] Redirect ke form import dengan pesan error flash
     */
    private function importError(string $message): void {
        $_SESSION['import_error'] = $message;
        header("Location: " . BASE_URL . "admin/import-csv");
        exit;
    }

    /**
     * Memproses file CSV yang diunggah Admin (Validasi Fail-Fast)
     */
    public function processImport(): void {
        $this->requireLogin();

        // 1. Validasi CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            $this->importError('Sesi tidak valid atau kadaluarsa.');
        }

        // 2. Validasi Kehadiran File dan Error Upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->importError('File gagal diunggah atau tidak ditemukan.');
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileSize = $_FILES['csv_file']['size'];

        // 3. Validasi Ukuran (Maks 5MB) dan Ekstensi
        if ($fileSize > 5 * 1024 * 1024) {
            $this->importError('Ukuran file maksimal 5MB.');
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $this->importError('Ekstensi file wajib .csv');
        }

        // 4. Validasi MIME Type secara Strict menggunakan finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mime, $allowedMimes)) {
            $this->importError('Tipe file tidak valid.');
        }

        // 5. Ekstraksi dan Validasi Konten CSV
        $rows = [];
        if (($handle = fopen($fileTmp, "r")) !== false) {
            // [2I] fgetcsv unlimited length
            // [2J] BOM strip + casing normalization
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
                fclose($handle);
                $this->importError('Format kolom CSV salah. Harus: nim, student_name, advisor_name, advisor_type');
            }

            $rowNumber = 2;
            while (($data = fgetcsv($handle, -1, ",")) !== false) {
                if (array_filter($data) === []) continue;

                if (count($data) !== 4) {
                    fclose($handle);
                    $this->importError("Baris ke-{$rowNumber}: jumlah kolom tidak valid.");
                }

                $type = trim($data[3]);
                if (!in_array($type, ['Wali', 'Magang', 'TA'])) {
                    fclose($handle);
                    $this->importError("Baris ke-{$rowNumber}: jenis pembimbing salah. Hanya: Wali, Magang, TA.");
                }

                $rows[] = [
                    'nim'          => trim($data[0]),
                    'student_name' => trim($data[1]),
                    'advisor_name' => trim($data[2]),
                    'advisor_type' => $type
                ];
                $rowNumber++;
            }
            fclose($handle);
        }

        // [2L] Max-row limit
        $maxRows = 50000;
        if (count($rows) > $maxRows) {
            $this->importError("File CSV melebihi batas maksimum {$maxRows} baris.");
        }

        // [2K] Deduplicate: per NIM+advisor_type, ambil yang terakhir
        $uniqueRows = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['nim'])) . '|' . trim($row['advisor_type']);
            $uniqueRows[$key] = $row;
        }
        $rows = array_values($uniqueRows);

        // 6. Jalankan Proses Transaksional di Model
        try {
            $model = new Advisor();
            $model->truncateAndReload($rows);
            // [2M] Regenerate CSRF token setelah import sukses
            regenerateCsrfToken();
            header("Location: " . BASE_URL . "admin/import-csv?status=success");
            exit;
        } catch (Exception $e) {
            $this->importError('Gagal menyimpan data ke database.');
        }
    }
}
