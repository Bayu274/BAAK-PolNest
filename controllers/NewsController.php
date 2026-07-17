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
    // AREA PUBLIK (FRONTEND)
    // ==========================================

    public function index() {
        $news = $this->newsModel->getAll();

        $this->render('frontend/home', [
            'title' => 'Beranda - BAAK Polnest',
            'news' => $news
        ]);
    }

    public function show($slug) {
        $news = $this->newsModel->getBySlug($slug);

        if (!$news) {
            die("Berita tidak ditemukan.");
        }

        $this->render('frontend/news-detail', ['news' => $news]);
    }

    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function listAdmin() {
        $this->requireLogin();

        $news = $this->newsModel->getAll();

        $this->render('backend/news-list', ['news' => $news]);
    }

    public function createForm() {
        $this->requireLogin();

        $this->render('backend/news-form');
    }

    public function store() {
        $this->requireLogin();

        if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        // Sanitasi XSS menggunakan htmlspecialchars sesuai PRD
        $title = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            die("Error: Judul dan Konten berita wajib diisi!");
        }

        // Generate slug (akan otomatis mengecek duplikat di DB)
        $slug = $this->generateSlug($title);

        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail_image']['name'])) {
            $uploadResult = $this->validateUpload($_FILES['thumbnail_image']);

            if (isset($uploadResult['error'])) {
                die("Gagal Upload: " . $uploadResult['error']);
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
            die("Berita tidak ditemukan.");
        }

        $this->render('backend/news-form', ['news' => $news, 'isEdit' => true]);
    }

    public function update() {
        $this->requireLogin();

        if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $id = $_POST['id'] ?? null;
        // Sanitasi XSS menggunakan htmlspecialchars sesuai PRD
        $title = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $content = trim($_POST['content'] ?? '');

        if (!$id || empty($title) || empty($content)) {
            die("Error: Data tidak lengkap.");
        }

        // Generate slug dengan pengecekan duplikat (mengabaikan slug milik ID ini sendiri)
        $slug = $this->generateSlug($title, $id);

        $oldNews = $this->newsModel->getById($id);
        $thumbnailPath = $oldNews['thumbnail_image'] ?? null;

        if (!empty($_FILES['thumbnail_image']['name'])) {
            $uploadResult = $this->validateUpload($_FILES['thumbnail_image']);

            if (isset($uploadResult['error'])) {
                die("Gagal Upload: " . $uploadResult['error']);
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

        header("Location: " . BASE_URL . "admin/news?status=updated");
        exit;
    }

    public function delete($id) {
        $this->requireLogin();

        if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            die("Akses ditolak: Token CSRF tidak valid!");
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
        }

        header("Location: " . BASE_URL . "admin/news?status=deleted");
        exit;
    }

    // ==========================================
    // HELPER FUNCTIONS (PRIVATE)
    // ==========================================

    private function generateSlug(string $title, ?int $ignoreId = null): string {
        // Buat slug dasar dari judul
        $originalSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = trim($originalSlug, '-');
        
        $slug = $originalSlug;
        $counter = 1;

        // Loop untuk mengecek duplikat di database
        while (true) {
            $existingNews = $this->newsModel->getBySlug($slug);
            
            // Jika tidak ada berita dengan slug tersebut, 
            // ATAU jika ini proses update dan slug tersebut milik berita ini sendiri, maka slug aman digunakan.
            if (!$existingNews || ($ignoreId !== null && $existingNews['id'] == $ignoreId)) {
                break;
            }

            // Jika slug sudah ada, tambahkan angka di belakangnya
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function validateUpload($file) {
        $allowedMimeTypes = ['image/jpeg', 'image/png'];
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

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('thumb_') . '.' . $extension;

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