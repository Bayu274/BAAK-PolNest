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

        $htmlContent = sanitizeHtmlContent($_POST['html_content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? null;

        // Konten dari Rich Text Editor sekarang disaring lewat HTML Purifier
        // (lihat sanitizeHtmlContent() di config/security.php) sebelum disimpan.

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