<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

class PageController extends Controller {

    // ==========================================
    // AREA PUBLIK (FRONTEND)
    // ==========================================
    
    public function show($identifier) {
    // 1. Panggil Model
    require_once __DIR__ . '/../models/Page.php';
    $pageModel = new Page();
    
    // 2. Ambil data dari database
    $dataPage = $pageModel->getByIdentifier($identifier); 
    
    // 3. (Opsional) Jika halaman tidak ada di database, tampilkan error
    if (!$dataPage) {
        die("404 - Halaman tidak ditemukan.");
    }

    // 4. Load view frontend dan kirim datanya
    $this->render('frontend/page-detail', ['page' => $dataPage]); 
    }

    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function editForm($identifier) {
    // PROTEKSI: Cek login admin (Method requireLogin akan dibuat oleh Dev 1)
    // $this->requireLogin();

    require_once __DIR__ . '/../models/Page.php';
    $pageModel = new Page();
    $dataPage = $pageModel->getByIdentifier($identifier);     
    
    // Load view backend (pages-edit.php) dan kirimkan datanya
    $this->render('backend/pages-edit', ['page' => $dataPage]);
    }

    public function save($identifier) {
        // PROTEKSI: Cek login admin
        // $this->requireLogin();

        // 1. Verifikasi CSRF Token secara ketat
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $htmlContent = $_POST['html_content'] ?? '';
        $adminId = $_SESSION['admin_id'] ?? null;

        // PERHATIAN: Karena input ini berasal dari Rich Text Editor, kita sengaja TIDAK menggunakan htmlspecialchars() 
        // agar struktur layout HTML (bold, list, tabel) tidak rusak saat dirender ulang.
        
        require_once __DIR__ . '/../models/Page.php';
        $pageModel = new Page();
        
        $success = $pageModel->updateContent($identifier, $htmlContent, $adminId);

        if ($success) {
            header("Location: /admin/pages/edit/" . $identifier . "?status=success");
            exit;
        } else {
            die("Gagal menyimpan perubahan konten halaman.");
        }
    }
}