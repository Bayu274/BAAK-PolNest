<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../models/News.php';

/**
 * NewsController
 * Menangani semua logika bisnis terkait Berita & Pengumuman (Sisi Publik & Admin)
 */
class NewsController extends Controller {

    private News $newsModel;

    public function __construct() {
        $this->newsModel = new News();
    }

    // ==========================================
    // HELPER: Flash message + redirect (mirip FileController::fileError)
    // ==========================================

    private function newsError(string $message, string $redirect): void {
        $_SESSION['import_error'] = $message;
        header("Location: " . BASE_URL . $redirect);
        exit;
    }

    // ==========================================
    // AREA PUBLIK (FRONTEND)
    // ==========================================

    public function show($slug) {
        $news = $this->newsModel->getBySlug($slug);

        if (!$news) {
            http_response_code(404);
            $this->render('frontend/news-detail', [
                'news' => ['title' => 'Tidak Ditemukan', 'content' => '<p>Berita yang Anda cari tidak ditemukan.</p>', 'created_at' => date('Y-m-d H:i:s'), 'thumbnail_image' => null]
            ]);
            return;
        }

        $this->render('frontend/news-detail', ['news' => $news]);
    }

    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function listAdmin() {
        $this->requireLogin();

        $news = $this->newsModel->getAll();

        $this->render('backend/news-list', [
            'news' => $news,
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    public function createForm() {
        $this->requireLogin();

        $this->render('backend/news-form', [
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    public function store() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            regenerateCsrfToken();
            $this->newsError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/news/create');
        }

        $title = trim($_POST['title'] ?? '');
        $content = sanitizeHtmlContent($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            $this->newsError('Judul dan konten berita wajib diisi.', 'admin/news/create');
        }

        $slug = $this->generateSlug($title);

        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail_image']['name'])) {
            $uploadResult = $this->validateUpload($_FILES['thumbnail_image']);

            if (isset($uploadResult['error'])) {
                $this->newsError($uploadResult['error'], 'admin/news/create');
            }
            $thumbnailPath = $uploadResult['path'];
        }

        $this->newsModel->create([
            'title'           => $title,
            'slug'            => $slug,
            'content'         => $content,
            'thumbnail_image' => $thumbnailPath,
            'created_by'      => $_SESSION['admin_id'] ?? null
        ]);

        regenerateCsrfToken();
        logInfo("News created: {$title} (admin_id: {$_SESSION['admin_id']})");

        header("Location: " . BASE_URL . "admin/news?status=success");
        exit;
    }

    public function editForm($id) {
        $this->requireLogin();

        if (!$id) {
            header("Location: " . BASE_URL . "admin/news");
            exit;
        }

        $news = $this->newsModel->getById($id);
        if (!$news) {
            $this->newsError('Berita tidak ditemukan.', 'admin/news');
        }

        $this->render('backend/news-form', [
            'news' => $news,
            'isEdit' => true,
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    public function update() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            regenerateCsrfToken();
            $id = $_POST['id'] ?? '';
            $this->newsError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/news/edit?id=' . $id);
        }

        $id = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $content = sanitizeHtmlContent($_POST['content'] ?? '');

        if (!$id || empty($title) || empty($content)) {
            $this->newsError('Data tidak lengkap. Judul dan konten wajib diisi.', 'admin/news/edit?id=' . $id);
        }

        $slug = $this->generateSlug($title);

        $oldNews = $this->newsModel->getById($id);
        $thumbnailPath = $oldNews['thumbnail_image'] ?? null;

        if (!empty($_FILES['thumbnail_image']['name'])) {
            $uploadResult = $this->validateUpload($_FILES['thumbnail_image']);

            if (isset($uploadResult['error'])) {
                $this->newsError($uploadResult['error'], 'admin/news/edit?id=' . $id);
            }

            if (!empty($oldNews['thumbnail_image'])) {
                $oldFilePath = __DIR__ . '/..' . $oldNews['thumbnail_image'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $thumbnailPath = $uploadResult['path'];
        }

        $this->newsModel->update([
            'id'              => $id,
            'title'           => $title,
            'slug'            => $slug,
            'content'         => $content,
            'thumbnail_image' => $thumbnailPath
        ]);

        regenerateCsrfToken();
        logInfo("News updated: id={$id}, title={$title} (admin_id: {$_SESSION['admin_id']})");

        header("Location: " . BASE_URL . "admin/news?status=updated");
        exit;
    }

    public function delete($id) {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            regenerateCsrfToken();
            $this->newsError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/news');
        }

        if ($id) {
            $news = $this->newsModel->getById($id);

            if ($news && !empty($news['thumbnail_image'])) {
                $filePath = __DIR__ . '/..' . $news['thumbnail_image'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->newsModel->delete($id);
            logInfo("News deleted: id={$id} (admin_id: {$_SESSION['admin_id']})");
        }

        regenerateCsrfToken();

        header("Location: " . BASE_URL . "admin/news?status=deleted");
        exit;
    }

    // ==========================================
    // HELPER FUNCTIONS (PRIVATE)
    // ==========================================

    private function generateSlug(string $title): string {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'berita-' . bin2hex(random_bytes(4));
        }

        // Cek keunikan slug
        $model = new News();
        $originalSlug = $slug;
        $counter = 2;
        while (true) {
            $existing = $model->getBySlug($slug);
            if (!$existing) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function validateUpload($file) {
        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Terjadi kesalahan sistem saat mengunggah file.'];
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'Ukuran gambar maksimal adalah 2MB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return ['error' => 'Format file tidak diizinkan. Harap unggah file JPG atau PNG.'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return ['error' => 'Ekstensi file tidak diizinkan.'];
        }
        $uniqueName = 'thumb_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $uploadDir = __DIR__ . '/../storage/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => '/storage/uploads/' . $uniqueName];
        }

        return ['error' => 'Gagal memindahkan gambar ke server.'];
    }
}
