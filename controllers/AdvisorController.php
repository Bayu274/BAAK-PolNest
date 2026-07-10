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
}
