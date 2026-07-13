<?php
/**
 * BAAK-PolNest - File Controller
 * Branch: feature/downloadable-files
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../models/DownloadableFile.php';

class FileController extends Controller {
    
    // Whitelist Kategori & Konstanta File
    private const FILE_CATEGORIES = ['jadwal_kuliah', 'kalender_akademik', 'formulir_krs', 'sop_dokumen', 'panduan_ta'];
    private const MAX_DOCUMENT_BYTES = 10 * 1024 * 1024; // 10 MB
    private const UPLOAD_DIR = __DIR__ . '/../storage/uploads/';

    /**
     * Menampilkan halaman Manajemen File
     */
    public function listAdmin(): void {
        $this->requireLogin(); // <-- DITAMBAHKAN: proteksi akses admin

        $model = new DownloadableFile();
        $files = $model->getActiveFiles();

        $this->render('backend/files-manage', [
            'page_title' => 'Manajemen File (PDF/DOCX)',
            'csrf_token' => generateCsrfToken(),
            'files' => $files,
            'categories' => self::FILE_CATEGORIES
        ]);
    }

    /**
     * Memproses Unggahan Dokumen dengan Validasi Berlapis
     */
    public function store(): void {
        $this->requireLogin(); // <-- DITAMBAHKAN: proteksi akses admin

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Error 403: CSRF Violation.");
        }

        $category = $_POST['file_category'] ?? '';
        if (!in_array($category, self::FILE_CATEGORIES)) {
            die("Error: Kategori file tidak valid.");
        }

        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            die("Error: File gagal diunggah.");
        }

        $fileTmp = $_FILES['document_file']['tmp_name'];
        $fileSize = $_FILES['document_file']['size'];
        $originalName = $_FILES['document_file']['name'];

        // 1. Validasi Ukuran (Maks 10MB)
        if ($fileSize > self::MAX_DOCUMENT_BYTES) {
            die("Error: Ukuran file melebihi batas 10MB.");
        }

        // 2. Validasi Ekstensi Fisik
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'docx'])) {
            die("Error: Hanya file PDF dan DOCX yang diizinkan.");
        }

        // 3. Validasi MIME Type secara Strict (Membaca bit dari dalam file, bukan header)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime, $allowedMimes)) {
            die("Error: Konten file tidak cocok dengan ekstensi dokumen yang sah.");
        }

        // 4. Pengacakan Nama File untuk Mencegah Enumerasi
        $randomName = 'doc_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destination = self::UPLOAD_DIR . $randomName;

        // 5. Eksekusi Simpan Fisik & Basis Data
        if (move_uploaded_file($fileTmp, $destination)) {
            try {
                $adminId = $_SESSION['admin_id'] ?? 1;
                
                $model = new DownloadableFile();
                $model->replaceByCategory($category, $originalName, $randomName, $adminId);

                header("Location: " . BASE_URL . "admin/files?status=success"); // <-- DIPERBAIKI: pakai BASE_URL
                exit;
            } catch (Exception $e) {
                unlink($destination);
                die("Error: Gagal menyimpan data ke database.");
            }
        } else {
            die("Error: Gagal memindahkan file ke direktori server.");
        }
    }

    /**
     * Menghapus (Soft Delete) file
     */
    public function delete(): void {
        $this->requireLogin(); // <-- DITAMBAHKAN: proteksi akses admin

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Error 403: CSRF Violation.");
        }

        $id = (int)($_POST['file_id'] ?? 0);
        if ($id > 0) {
            $model = new DownloadableFile();
            $model->deactivate($id);
        }
        
        header("Location: " . BASE_URL . "admin/files?status=deleted"); // <-- DIPERBAIKI: pakai BASE_URL
        exit;
    }
}