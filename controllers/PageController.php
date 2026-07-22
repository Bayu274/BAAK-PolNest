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
// ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function listAdmin() {
        $this->requireLogin();
        $pageModel = new Page();
        $pages = $pageModel->getAll(); 

        $this->render('backend/pages-list', [
            'pages' => $pages,
            'csrf_token' => generateCsrfToken() // Tambahan token untuk tombol hapus
        ], true);
    }

    // METHOD BARU: Menampilkan form tambah halaman
    public function createForm() {
        $this->requireLogin();
        $this->render('backend/pages-create', [
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    // METHOD BARU: Menyimpan halaman baru ke database
    public function store() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $identifier = trim($_POST['identifier'] ?? '');
        $title = trim($_POST['title'] ?? '');
        
        if (empty($identifier)) {
            die("Error: Identifier tidak boleh kosong.");
        }
        
        // Memastikan identifier bersih (hanya huruf kecil dan strip)
        $identifier = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $identifier));
        $identifier = trim($identifier, '-');

        $htmlContent = sanitizeHtmlContent($_POST['html_content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? null;

        $pageModel = new Page();
        
        // Mencegah duplikasi identifier
        if ($pageModel->getByIdentifier($identifier)) {
            die("Error: Identifier sudah digunakan. Silakan gunakan yang lain.");
        }

        $pageModel->create($identifier, $title, $htmlContent, $adminId);

        header("Location: " . BASE_URL . "admin/pages?status=created");
        exit;
    }

    // METHOD BARU: Menghapus halaman
    public function delete() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $identifier = $_POST['identifier'] ?? '';
        if (empty($identifier)) {
            die("Error: Identifier tidak valid.");
        }

        $pageModel = new Page();
        $pageModel->delete($identifier);

        header("Location: " . BASE_URL . "admin/pages?status=deleted");
        exit;
    }

    public function editForm($identifier) {
        $this->requireLogin();

        $pageModel = new Page();
        $page = $pageModel->getByIdentifier($identifier);

        $this->render('backend/pages-edit', [
            'page' => $page,
            'identifier' => $identifier,
            'csrf_token' => generateCsrfToken()
        ], true); 
    }

    public function save($identifier) {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            die("Akses ditolak: Token CSRF tidak valid!");
        }

        $pageModel = new Page();

        // Cek dulu apakah identifier ini benar-benar ada di database,
        // supaya kita bisa membedakan "identifier tidak ditemukan" (gagal sungguhan)
        // dari "tidak ada perubahan konten" (bukan kegagalan).
        $existingPage = $pageModel->getByIdentifier($identifier);
        if (!$existingPage) {
            die("Error: Halaman dengan identifier \"" . htmlspecialchars($identifier) . "\" tidak ditemukan di database.");
        }

        $htmlContent = sanitizeHtmlContent($_POST['html_content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? null;

        // Konten dari Rich Text Editor sekarang disaring lewat HTML Purifier
        // (lihat sanitizeHtmlContent() di config/security.php) sebelum disimpan.

        // updateContent() bisa return false kalau tidak ada baris yang benar-benar berubah
        // (misal konten yang disubmit identik dengan yang sudah tersimpan) —
        // itu BUKAN kegagalan, karena identifier-nya sudah kita pastikan ada di atas.
        $pageModel->updateContent($identifier, $htmlContent, $adminId);

        header("Location: " . BASE_URL . "admin/pages/edit/" . $identifier . "?status=success");
        exit;
    }
}