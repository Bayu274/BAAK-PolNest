<?php

// Pastikan file ini tidak bisa diakses langsung tanpa routing
if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

/**
 * NewsController
 * Menangani semua logika bisnis terkait Berita & Pengumuman (Sisi Publik & Admin)
 */
class NewsController extends Controller {

    public function __construct() {
        // Nanti kita akan load model News di sini
        // $this->newsModel = $this->model('News');
    }

    // ==========================================
    // AREA PUBLIK (FRONTEND)
    // ==========================================

    public function index() {
        // TODO: Ambil semua data berita dari database
        // $news = $this->newsModel->getAll();
        
        // Load view frontend (home.php)
        $this->render('frontend/home', [
            'title' => 'Beranda - BAAK Polnest'
            // 'news' => $news
        ]);
    }

    public function show($slug) {
        // TODO: Ambil detail berita berdasarkan slug
        
        // Load view frontend (news-detail.php)
        $this->render('frontend/news-detail');
    }


    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function listAdmin() {
        // PROTEKSI: Cek login admin (Method requireLogin akan dibuat Dev 1)
        // $this->requireLogin();
        
        // Load view backend (news-list.php)
        $this->render('backend/news-list');
    }

    public function createForm() {
        // PROTEKSI: Cek login admin
        // $this->requireLogin();
        
        $this->render('backend/news-form');
    }

    public function store() {
        // 1. Verifikasi CSRF Token
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        // 2. Tangkap Input
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? ''; // Dibiarkan mentah karena dari TinyMCE/CKEditor

        if (empty($title) || empty($content)) {
            die("Error: Judul dan Konten berita wajib diisi!");
        }

        // Sanitasi Judul (wajib htmlspecialchars untuk mencegah XSS)
        $titleSanitized = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        // 3. Generate Slug dari Judul
        $slug = $this->generateSlug($titleSanitized);

        // 4. Proses Upload Thumbnail (Opsional)
        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail']['name'])) {
            $uploadResult = $this->validateUpload($_FILES['thumbnail']);
            
            // Jika ada error dari helper validasi upload
            if (isset($uploadResult['error'])) {
                die("Gagal Upload: " . $uploadResult['error']);
            }
            $thumbnailPath = $uploadResult['path'];
        }

        // 5. Simpan ke Database via Model News
        // Asumsi base controller memiliki method load model, atau kita require manual
        require_once __DIR__ . '/../models/News.php';
        $newsModel = new News();

        $newsId = $newsModel->create([
            'title'           => $titleSanitized,
            'slug'            => $slug,
            'content'         => $content,
            'thumbnail_image' => $thumbnailPath,
            'created_by'      => $_SESSION['admin_id'] ?? null // Audit trail
        ]);

        // 6. Redirect kembali ke halaman list berita
        header("Location: /admin/news?status=success");
        exit;
    }

    public function editForm($id) {
        // TODO: Ambil data berita berdasarkan ID lalu kirim ke view form
    }

    public function updateNews($id) {
        // TODO: Proses update data ke database
    }

    public function destroy($id) {
        // TODO: Proses hapus data dari database (termasuk hapus file gambar fisiknya)
    }

    // ==========================================
    // HELPER FUNCTIONS (PRIVATE)
    // ==========================================

    private function validateUpload($file) {
        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // Maksimal 2MB

        // Cek apakah ada error dari sisi server PHP saat upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Terjadi kesalahan sistem saat mengunggah file.'];
        }

        // Cek Ukuran File
        if ($file['size'] > $maxSize) {
            return ['error' => 'Ukuran gambar maksimal adalah 2MB.'];
        }

        // Keamanan Ekstra: Cek tipe MIME file yang sebenarnya (bukan hanya dari nama ekstensinya)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return ['error' => 'Format file tidak diizinkan. Harap unggah file JPG atau PNG.'];
        }

        // Generate nama file unik agar file lama tidak tertimpa
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid('thumb_') . '.' . $extension;
        
        // Tentukan lokasi penyimpanan (folder /storage/uploads di root folder)
        $uploadDir = __DIR__ . '/../storage/uploads/';
        
        // Buat foldernya jika ternyata belum ada secara fisik
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $uniqueName;

        // Pindahkan file dari temporary ke folder utama
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Kembalikan path relatif untuk disimpan ke database MySQL
            return ['path' => '/storage/uploads/' . $uniqueName];
        }

        return ['error' => 'Gagal memindahkan gambar ke server.'];
    }
}