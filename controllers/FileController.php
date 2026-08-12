<?php
/**
 * BAAK-PolNest - File Controller
 * Branch: feature/downloadable-files
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../models/DownloadableFile.php';

class FileController extends Controller {
    
    // Kategori bawaan sebagai SARAN (datalist) — admin bebas memakai kategori
    // yang sudah ada atau mengetik kategori baru (kustom). Validasi ketat:
    // huruf kecil, angka, underscore, maks 100 karakter.
    private const SUGGESTED_CATEGORIES = [
        'kalender_akademik',
        'jadwal_kuliah',
        'formulir_krs',
        'sop_dokumen',
        'panduan_ta',
        'form_cuti',
        'form_pindah_kelas',
        'form_mengundurkan_diri'
    ];
    private const MAX_DOCUMENT_BYTES = 10 * 1024 * 1024; // 10 MB
    private const UPLOAD_DIR = __DIR__ . '/../storage/uploads/';

    /**
     * Menggabungkan kategori saran (bawaan) dengan kategori yang pernah
     * dipakai di database untuk mengisi datalist form upload.
     */
    private static function getCategorySuggestions(DownloadableFile $model): array {
        $dbCategories = $model->getDistinctCategories();
        return array_values(array_unique(array_merge(self::SUGGESTED_CATEGORIES, $dbCategories)));
    }

    /**
     * Redirect ke form files dengan pesan error flash
     */
    private function fileError(string $message): void {
        $_SESSION['import_error'] = $message;
        header("Location: " . BASE_URL . "admin/files");
        exit;
    }

    /**
     * Memastikan lokasi fisik file yang akan diunduh/dihapus.
     *
     * Nilai file_path di database bisa berupa nama file polos ("doc_x.pdf",
     * default aplikasi), path relatif ("storage/uploads/doc_x.pdf", mis. hasil
     * import manual di phpMyAdmin), maupun path absolut (konfigurasi lama).
     * Fungsi ini mencoba semua bentuk dan mengembalikan path nyata yang bisa
     * dibaca, atau null bila file fisiknya tidak ada.
     */
    private function resolvePhysicalFile(array $file): ?string {
        $raw = trim((string)($file['file_path'] ?? ''));
        if ($raw === '') {
            return null;
        }

        $candidates = [];
        $candidates[] = self::UPLOAD_DIR . basename($raw);

        // Path absolut (Unix "/.../" atau Windows "C:\...")
        if (str_starts_with($raw, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $raw)) {
            $candidates[] = $raw;
        } else {
            // Path relatif dengan sub direktori, mis. "storage/uploads/doc_x.pdf"
            $candidates[] = self::UPLOAD_DIR . ltrim(str_replace('\\', '/', $raw), '/');
        }

        foreach ($candidates as $cand) {
            if (is_file($cand) && is_readable($cand)) {
                return realpath($cand) ?: $cand;
            }
        }

        return null;
    }

    /**
     * Menampilkan halaman Manajemen File
     */
    public function listAdmin(): void {
        $this->requireLogin();

        $model = new DownloadableFile();
        $files = $model->getActiveFiles();
        if (empty($files) && $model->getLastError() !== null) {
            logError("listAdmin: getActiveFiles gagal — " . $model->getLastError());
        }

        $this->render('backend/files-manage', [
            'page_title' => 'Manajemen File (PDF/DOCX)',
            'csrf_token' => generateCsrfToken(),
            'files' => $files,
            'categories' => self::getCategorySuggestions($model)
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

        $category = trim($_POST['file_category'] ?? '');
        if (!preg_match('/^[a-z0-9_]{1,100}$/', $category)) {
            $this->fileError('Kategori file tidak valid. Gunakan huruf kecil, angka, dan underscore (_) saja (maks 100 karakter).');
        }

        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErr = $_FILES['document_file']['error'] ?? 'file tidak dikirim';
            logError("Upload file gagal: upload error code={$uploadErr}");
            $this->fileError('File gagal diunggah. Kode error: ' . $uploadErr);
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

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $fileTmp);
            finfo_close($finfo);

            $allowedMimes = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($mime, $allowedMimes)) {
                $this->fileError('Konten file tidak valid.');
            }
        } else {
            // Ekstensi fileinfo tidak tersedia di server — validasi mengandalkan
            // ekstensi file (sudah dicek di atas) sebagai fallback.
            logWarning("finfo tidak tersedia — validasi MIME dilewati (fallback ke ekstensi file).");
        }

        $randomName = 'doc_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destination = self::UPLOAD_DIR . $randomName;

        if (move_uploaded_file($fileTmp, $destination)) {
            try {
                $adminId = $_SESSION['admin_id'];

                $model = new DownloadableFile();
                $title = $_POST['file_title'] ?? '';
                $saved = $model->addFile($category, $originalName, $randomName, $adminId, $title !== '' ? $title : null);

                if (!$saved) {
                    unlink($destination);
                    $dbErr = $model->getLastError();
                    logError("File upload DB error: addFile gagal (category: {$category}) — " . ($dbErr ?? 'unknown'));
                    $detail = (APP_ENV === 'development' && $dbErr !== null) ? ' Detail: ' . $dbErr : '';
                    $this->fileError('Gagal menyimpan data ke database.' . $detail);
                }

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
            $lastErr = error_get_last();
            logError("move_uploaded_file gagal ke {$destination}: " . ($lastErr['message'] ?? 'unknown'));
            $this->fileError('Gagal memindahkan file ke server.');
        }
    }

    /**
     * Menghapus file (Hard Delete): hapus baris di database DAN hapus file
     * fisik di server supaya benar-benar tidak bisa diunduh lagi.
     */
    public function delete(): void {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            $this->fileError('CSRF token tidak valid.');
        }

        $id = (int)($_POST['file_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            logError("Delete file gagal: file_id kosong/tidak valid. POST keys: " . implode(',', array_keys($_POST)));
            $this->fileError('ID file tidak valid.');
        }

        $model = new DownloadableFile();
        $file = $model->getById($id, false);

        if ($file !== null) {
            // Hapus file fisik (path di-resolve dari file_path database)
            $physical = $this->resolvePhysicalFile($file);
            if ($physical !== null) {
                if (unlink($physical)) {
                    logInfo("File fisik dihapus: " . basename($physical));
                } else {
                    logError("Gagal menghapus file fisik: {$physical}");
                }
            } else {
                logInfo("Delete file id={$id}: file fisik tidak ditemukan di disk (file_path DB: {$file['file_path']}). Baris database tetap dihapus.");
            }
        } else {
            logError("Delete file id={$id}: baris tidak ditemukan di database. DB error: " . ($model->getLastError() ?? 'none'));
        }

        // Hapus baris database (no-op bila barisnya sudah tidak ada)
        $model->deleteById($id);

        regenerateCsrfToken();
        logInfo("File deleted (hard): id={$id} (admin_id: {$_SESSION['admin_id']})");
        $this->cleanupOrphanedFiles();

        header("Location: " . BASE_URL . "admin/files?status=deleted");
        exit;
    }

    /**
     * Endpoint unduhan publik (GET /files/download/{id}).
     * Memaksa browser mengunduh (Content-Disposition: attachment) sehingga
     * file langsung terdownload saat diklik, bukan dibuka di tab baru.
     */
    public function download($id): void {
        $id = is_numeric($id) ? (int) $id : 0;
        if ($id <= 0) {
            $this->notFound();
        }

        $model = new DownloadableFile();
        $file = $model->getById($id);

        if ($file === null) {
            logError("download id={$id}: getById null. DB error: " . ($model->getLastError() ?? 'none'));
            $this->notFound();
        }

        $physical = $this->resolvePhysicalFile($file);
        if ($physical === null) {
            logError("download id={$id}: file fisik tidak ditemukan di disk (file_path DB: " . ($file['file_path'] ?? '-') . ")");
            $this->notFound();
        }

        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        $downloadName = preg_replace('/["\r\n]/', '', $file['file_name']);
        $downloadName = ($downloadName === '') ? 'dokumen.' . ($ext !== '' ? $ext : 'pdf') : $downloadName;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('Content-Length: ' . filesize($physical));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        readfile($physical);
        exit;
    }

    /**
     * Merespons halaman publik 404 sederhana untuk file yang tidak ditemukan.
     */
    private function notFound(): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>404 - File Tidak Ditemukan</title></head>';
        echo '<body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa;">';
        echo '<div style="text-align:center;"><h1 style="color:#dc3545;">404</h1><p>File tidak ditemukan atau sudah dihapus.</p>';
        echo '<a href="' . htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') . 'jadwal" style="color:#0d6efd;">Kembali ke Jadwal & Pedoman</a></div></body></html>';
        exit;
    }

    /**
     * Membersihkan file fisik yang sudah tidak ada di database
     *
     * PENTING: perbandingan dilakukan terhadap basename. file_path di database
     * bisa saja menyimpan path berprefix ("storage/uploads/doc_x.pdf") — tanpa
     * basename, file aktif justru dianggap orphan dan ikut terhapus, sehingga
     * baris database masih ada tapi file fisik hilang (download 404).
     */
    private function cleanupOrphanedFiles(): void {
        $uploadsDir = self::UPLOAD_DIR;
        $model = new DownloadableFile();

        $activeFiles = $model->getActiveFileNames();
        $activePaths = array_column($activeFiles, 'file_path');
        $activeBasenames = array_map('basename', $activePaths);

        $files = glob($uploadsDir . 'doc_*');
        foreach ($files as $file) {
            $filename = basename($file);
            if (!in_array($filename, $activeBasenames, true)) {
                if (filemtime($file) < time() - (7 * 24 * 3600)) {
                    unlink($file);
                    logInfo("Cleaned orphaned file: {$filename}");
                }
            }
        }
    }
}
