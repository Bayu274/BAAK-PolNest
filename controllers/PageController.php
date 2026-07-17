<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../models/Page.php';

class PageController extends Controller {

    // ==========================================
    // AREA PUBLIK (FRONTEND)
    // ==========================================

    public function show($identifier) {
        $pageModel = new Page();
        $page = $pageModel->getByIdentifier($identifier);

        if (!$page) {
            die("Halaman tidak ditemukan.");
        }

        $this->render('frontend/page-detail', ['page' => $page]);
    }

    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================
    public function listAdmin() {
        $this->requireLogin();
        
        $pageModel = new Page();
        // Memanggil fungsi getAll() dari model Page
        $pages = $pageModel->getAll(); 
        
        $this->render('backend/pages-list', [
            'pages' => $pages
        ]);
    }
    public function editForm($identifier) {
        $this->requireLogin();

        $pageModel = new Page();
        $page = $pageModel->getByIdentifier($identifier);

        $this->render('backend/pages-edit', [
            'page' => $page,
            'identifier' => $identifier,
            'csrf_token' => generateCsrfToken()
        ]);
    }

    public function save($identifier) {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $htmlContent = $_POST['html_content'] ?? '';
        $adminId = $_SESSION['admin_id'] ?? null;

        // PERHATIAN: konten dari Rich Text Editor sengaja TIDAK di-htmlspecialchars()
        // supaya struktur HTML (bold, list, tabel) tidak rusak saat dirender ulang.
        // Masih titik terbuka yang perlu dibahas tim — lihat catatan sanitasi HTML.

        $pageModel = new Page();
        $success = $pageModel->updateContent($identifier, $htmlContent, $adminId);

        if ($success) {
            header("Location: " . BASE_URL . "admin/pages/edit/" . $identifier . "?status=success");
            exit;
        } else {
            die("Gagal menyimpan perubahan konten halaman.");
        }
    }
}