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
     * Menampilkan halaman review Data Pembimbing langsung dari database
     * (real-time). Admin bisa mengaudit data tersimpan tanpa membuka file CSV.
     * Menu sidebar "Data Pembimbing" mengarah ke halaman ini; import CSV
     * tetap terpisah via tombol menuju /admin/import-csv.
     */
    public function listData(): void {
        $this->requireLogin();

        // Pagination: page wajib angka positif, dibatasi agar tidak over-inject.
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $page = max(1, $page);

        $filters = [
            'keyword'      => isset($_GET['q']) ? mb_substr(trim((string) $_GET['q']), 0, 100) : '',
            'advisor_type' => '',
        ];

        $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
        if (in_array($type, ['Wali', 'Magang', 'TA'], true)) {
            $filters['advisor_type'] = $type;
        }

        $model   = new Advisor();
        $perPage = 30;

        $total      = $model->countRecords($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $records = $model->getRecords($filters, $perPage, $offset);
        $stats   = $model->getStats();

        $this->render('backend/advisor-data', [
            'page_title' => 'Data Pembimbing',
            'csrf_token' => generateCsrfToken(),
            'records'    => $records,
            'stats'      => $stats,
            'filter'     => $filters,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ], true);
    }

    /**
     * Mengunduh template CSV untuk staf BAAK (Phase 13)
     * Hanya admin yang login yang dapat mengunduh.
     */
    public function downloadTemplate(): void {
        $this->requireLogin();

        $templatePath = __DIR__ . '/../storage/templates/template_dosen_pembimbing.csv';

        if (!file_exists($templatePath)) {
            $this->importError('Template CSV tidak ditemukan. Hubungi pengembang.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_dosen_pembimbing.csv"');
        header('Content-Length: ' . filesize($templatePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        readfile($templatePath);
        exit;
    }

    /**
     * Mengunduh template Excel (.xlsx) dengan format "satu sheet berisi
     * beberapa tabel" untuk staf BAAK (request klien).
     * Hanya admin yang login yang dapat mengunduh.
     */
    public function downloadTemplateXlsx(): void {
        $this->requireLogin();

        $templatePath = __DIR__ . '/../storage/templates/template_dosen_pembimbing.xlsx';

        if (!file_exists($templatePath)) {
            $this->importError('Template Excel tidak ditemukan. Hubungi pengembang.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_dosen_pembimbing.xlsx"');
        header('Content-Length: ' . filesize($templatePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        readfile($templatePath);
        exit;
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
        if (!in_array($ext, ['csv', 'xlsx'])) {
            $this->importError('Ekstensi file wajib .csv atau .xlsx');
        }

        // 4. Validasi MIME Type secara Strict menggunakan finfo (bila tersedia)
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $fileTmp);
            finfo_close($finfo);

            if ($ext === 'xlsx') {
                // .xlsx adalah arsip zip — finfo bisa melaporkan beberapa MIME berbeda
                $allowedMimes = [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip',
                    'application/x-zip-compressed',
                    'application/octet-stream',
                ];
            } else {
                $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
            }

            if (!in_array($mime, $allowedMimes)) {
                $this->importError('Tipe file tidak valid.');
            }
        } else {
            // finfo tidak tersedia — validasi mengandalkan ekstensi file
            logWarning("finfo tidak tersedia — validasi MIME dilewati (fallback ke ekstensi file).");
        }

        // 5. Ekstraksi dan Validasi Konten (CSV atau XLSX multi-tabel)
        $rows = ($ext === 'xlsx')
            ? $this->parseXlsxRows($fileTmp)
            : $this->parseCsvRows($fileTmp);

        // [2L] Max-row limit
        $maxRows = 50000;
        if (count($rows) > $maxRows) {
            $this->importError("File melebihi batas maksimum {$maxRows} baris.");
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
            header("Location: " . BASE_URL . "admin/data-pembimbing?status=imported");
            exit;
        } catch (Exception $e) {
            $this->importError('Gagal menyimpan data ke database.');
        }
    }

    /**
     * Mem-parsing file CSV dengan validasi per baris.
     * Memanggil importError() (exit) bila format tidak valid.
     *
     * @return array<int, array{nim:string, student_name:string, advisor_name:string, advisor_type:string}>
     */
    private function parseCsvRows(string $fileTmp): array {
        $rows = [];

        if (($handle = fopen($fileTmp, "r")) === false) {
            $this->importError('File CSV tidak dapat dibaca.');
        }

        // [2I] fgetcsv unlimited length
        // [2J] BOM strip + casing normalization
        $header = fgetcsv($handle, null, ",");

        if ($header === false) {
            fclose($handle);
            $this->importError('File CSV kosong.');
        }

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
        while (($data = fgetcsv($handle, null, ",")) !== false) {
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

        return $rows;
    }

    /**
     * Mem-parsing file Excel (.xlsx) dengan format "satu sheet berisi
     * beberapa tabel" (request klien): setiap tabel diawali baris header
     * nim, student_name, advisor_name, advisor_type; baris judul/pemisah
     * antar tabel dibolehkan dan dilewati otomatis.
     *
     * Memanggil importError() (exit) bila format tidak valid.
     *
     * @return array<int, array{nim:string, student_name:string, advisor_name:string, advisor_type:string}>
     */
    private function parseXlsxRows(string $fileTmp): array {
        require_once __DIR__ . '/../libs/XlsxReader.php';

        try {
            $sheetRows = XlsxReader::read($fileTmp);
        } catch (Throwable $e) {
            $this->importError('File Excel tidak valid: ' . $e->getMessage());
        }

        if ($sheetRows === []) {
            $this->importError('File Excel kosong atau tidak memiliki baris data.');
        }

        $expectedHeader = ['nim', 'student_name', 'advisor_name', 'advisor_type'];
        $rows = [];

        foreach ($sheetRows as $rowIndex => $cells) {
            $clean = [];
            foreach ($cells as $cell) {
                $clean[] = trim((string) $cell);
            }

            // Baris kosong (mis. pemisah antar tabel) → lewati
            if (array_filter($clean) === []) {
                continue;
            }

            $rowNumber = $rowIndex + 1;
            $cleanCount = count($clean);

            // Baris header → penanda tabel baru (validate bentuknya)
            $firstCell = strtolower($clean[0] ?? '');
            if ($firstCell === 'nim') {
                $header = array_map(function($h) {
                    return strtolower(trim($h));
                }, array_slice($clean, 0, 4));
                if ($header !== $expectedHeader) {
                    $this->importError("Baris ke-{$rowNumber}: format header tabel salah. Harus: nim, student_name, advisor_name, advisor_type");
                }
                continue;
            }

            // Baris dengan kolom < 4 → judul/pemisah antar tabel, lewati
            if ($cleanCount < 4) {
                continue;
            }

            if ($cleanCount > 4) {
                $this->importError("Baris ke-{$rowNumber}: jumlah kolom tidak valid.");
            }

            $type = $clean[3];
            if (!in_array($type, ['Wali', 'Magang', 'TA'])) {
                $this->importError("Baris ke-{$rowNumber}: jenis pembimbing salah. Hanya: Wali, Magang, TA.");
            }

            $rows[] = [
                'nim'          => $clean[0],
                'student_name' => $clean[1],
                'advisor_name' => $clean[2],
                'advisor_type' => $type
            ];
        }

        if ($rows === []) {
            $this->importError('File Excel tidak mengandung data pembimbing yang valid. Pastikan setiap tabel memiliki header nim, student_name, advisor_name, advisor_type.');
        }

        return $rows;
    }
}
