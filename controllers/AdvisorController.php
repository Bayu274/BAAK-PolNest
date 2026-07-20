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
        // Memanggil objek render bawaan base Controller yang dibuat Dev 1
        $this->render('frontend/search-dosen', [
            'page_title' => 'Pencarian Dosen Pembimbing'
        ]);
    }

    /**
     * Memproses pencarian data via AJAX Fetch API (POST /api/advisors/search)
     */
    public function search(): void {
        // 1. Ambil IP Client untuk keperluan rate-limiting kelak
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 2. Proteksi Rate-Limiting Granular (Pencarian maksimal 10x per menit)
        if (!checkRateLimit($ipAddress, 'search_advisor', 10, 60)) {
            $this->sendJson(429, [
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'
            ]);
        }

        // 3. Baca payload mentah JSON POST dari Client
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true);

        $nim = isset($payload['nim']) ? trim($payload['nim']) : '';
        $name = isset($payload['student_name']) ? trim($payload['student_name']) : '';

        // 4. Validasi Dasar Input
        if ($nim === '' || $name === '') {
            $this->sendJson(400, [
                'status' => 'error',
                'message' => 'NIM dan Nama Lengkap wajib diisi.'
            ]);
        }

        // 5. Query data melalui Model Advisor
        $model = new Advisor();
        $results = $model->findByNimAndName($nim, $name);

        // 6. Evaluasi Hasil (Pesan Error Seragam/Uniform untuk mitigasi Brute-Force Enumeration)
        if (empty($results)) {
            $this->sendJson(404, [
                'status' => 'error',
                'message' => 'Data tidak ditemukan atau kecocokan tidak valid.'
            ]);
        }

        // 7. Berhasil, kirim data terfilter
        $this->sendJson(200, [
            'status' => 'success',
            'data' => $results
        ]);
    }

    /**
     * Standarisasi pengiriman JSON response yang bersih dan aman
     */
    private function sendJson(int $statusCode, array $data): void {
        // Bersihkan output buffer liar yang berpotensi merusak struktur JSON
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

        // Asumsi base Controller Dev 1 memiliki render khusus untuk backend (layout)
        $this->render('backend/advisor-import', [
            'page_title' => 'Impor Data Dosen Pembimbing',
            // Panggil token CSRF dari security.php (wajib)
            'csrf_token' => generateCsrfToken()
        ]);
    }

    /**
     * Memproses file CSV yang diunggah Admin (Validasi Fail-Fast)
     */
    public function processImport(): void {
        $this->requireLogin();

        // 1. Validasi CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Error 403: Sesi tidak valid atau kadaluarsa (CSRF Violation).");
        }

        // 2. Validasi Kehadiran File dan Error Upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            die("Error: File gagal diunggah atau tidak ditemukan.");
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $fileSize = $_FILES['csv_file']['size'];

        // 3. Validasi Ukuran (Maks 5MB) dan Ekstensi
        if ($fileSize > 5 * 1024 * 1024) {
            die("Error: Ukuran file maksimal 5MB.");
        }
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            die("Error: Ekstensi file wajib .csv");
        }

        // 4. Validasi MIME Type secara Strict menggunakan finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mime, $allowedMimes)) {
            die("Error: Tipe file tidak dikenali sebagai format teks CSV yang valid.");
        }

        // 5. Ekstraksi dan Validasi Konten CSV
        $rows = [];
        if (($handle = fopen($fileTmp, "r")) !== false) {
            // Ambil baris pertama sebagai Header
            $header = fgetcsv($handle, 1000, ",");
            
            // Validasi struktur Header
            $expectedHeader = ['nim', 'student_name', 'advisor_name', 'advisor_type'];
            if ($header !== $expectedHeader) {
                fclose($handle);
                die("Error: Format kolom CSV salah. Harus persis: nim, student_name, advisor_name, advisor_type");
            }

            // Baca isi baris per baris
            $rowNumber = 2; // Baris 1 adalah header
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                // Lewati baris kosong
                if (array_filter($data) === []) continue;

                if (count($data) !== 4) {
                    fclose($handle);
                    die("Error: Baris ke-{$rowNumber} memiliki jumlah kolom yang tidak valid.");
                }

                $type = trim($data[3]);
                if (!in_array($type, ['Wali', 'Magang', 'TA'])) {
                    fclose($handle);
                    die("Error: Baris ke-{$rowNumber} memiliki jenis pembimbing yang salah. Hanya diizinkan: Wali, Magang, TA.");
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

        // 6. Jalankan Proses Transaksional di Model
        try {
            $model = new Advisor();
            $model->truncateAndReload($rows);
            // Redirect kembali dengan pesan sukses (menggunakan session flash jika ada di base controller Dev 1)
            header("Location: " . BASE_URL . "admin/import-csv?status=success");
            exit;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}