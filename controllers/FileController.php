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
     * Redirect ke form files dengan pesan error flash
     */
    private function fileError(string $message): void {
        $_SESSION['import_error'] = $message;
        header("Location: " . BASE_URL . "admin/files");
        exit;
    }

    /**
     * Menampilkan halaman Manajemen File
     */
    public function listAdmin(): void {
        $this->requireLogin();

        $model = new DownloadableFile();
        $files = $model->getActiveFiles();

        $this->render('backend/files-manage', [
            'page_title' => 'Manajemen File (PDF/DOCX)',
            'csrf_token' => generateCsrfToken(),
            'files' => $files,
            'categories' => self::FILE_CATEGORIES
        ], true);
    }

    /**
     * Memproses Unggahan Dokumen dengan Validasi Berlapis
     */
    public function store(): void {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            $this->fileError('CSRF token tidak valid.');
        }

        $category = $_POST['file_category'] ?? '';
        if (!in_array($category, self::FILE_CATEGORIES)) {
            $this->fileError('Kategori file tidak valid.');
        }

        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $this->fileError('File gagal diunggah.');
        }

        $fileTmp = $_FILES['document_file']['tmp_name'];
        $fileSize = $_FILES['document_file']['size'];
        $originalName = $_FILES['document_file']['name'];

        if ($fileSize > self::MAX_DOCUMENT_BYTES) {
            $this->fileError('Ukuran file maksimal 10MB.');
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'docx'])) {
            $this->fileError('Hanya file PDF dan DOCX yang diizinkan.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime, $allowedMimes)) {
            $this->fileError('Konten file tidak valid.');
        }

        $randomName = 'doc_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destination = self::UPLOAD_DIR . $randomName;

        if (move_uploaded_file($fileTmp, $destination)) {
            try {
                $adminId = $_SESSION['admin_id'];
                
                $model = new DownloadableFile();
                $model->replaceByCategory($category, $originalName, $randomName, $adminId);

                regenerateCsrfToken();
                logInfo("File uploaded: {$originalName} (category: {$category}, admin_id: {$adminId})");

                header("Location: " . BASE_URL . "admin/files?status=success");
                exit;
            } catch (Throwable $e) {
                unlink($destination);
                logError("File upload DB error: " . $e->getMessage());
                $this->fileError('Gagal menyimpan data ke database.');
            }
        } else {
            $this->fileError('Gagal memindahkan file ke server.');
        }
    }

    /**
     * Menghapus (Soft Delete) file
     */
    public function delete(): void {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            $this->fileError('CSRF token tidak valid.');
        }

        $id = (int)($_POST['file_id'] ?? 0);
        if ($id > 0) {
            $model = new DownloadableFile();
            $model->deactivate($id);

            regenerateCsrfToken();
            logInfo("File deactivated: id={$id} (admin_id: {$_SESSION['admin_id']})");
        }
        
        header("Location: " . BASE_URL . "admin/files?status=deleted");
        exit;
    }

    /**
     * Membersihkan file fisik yang sudah tidak ada di database
     */
    private function cleanupOrphanedFiles(): void {
        $uploadsDir = self::UPLOAD_DIR;
        $model = new DownloadableFile();
        
        $activeFiles = $model->getActiveFileNames();
        $activePaths = array_column($activeFiles, 'file_path');
        
        $files = glob($uploadsDir . 'doc_*');
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $activePaths)) {
                if (filemtime($file) < time() - (7 * 24 * 3600)) {
                    unlink($file);
                    logInfo("Cleaned orphaned file: {$filename}");
                }
            }
        }
    }
}
