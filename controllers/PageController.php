<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../models/Page.php';

class PageController extends Controller {

    // ==========================================
    // HELPER: Flash message + redirect
    // ==========================================

    private function pageError(string $message, string $redirect): void {
        $_SESSION['import_error'] = $message;
        header("Location: " . BASE_URL . $redirect);
        exit;
    }

    // ==========================================
    // AREA PUBLIK (FRONTEND)
    // ==========================================

    public function show($identifier) {
        $pageModel = new Page();
        $page = $pageModel->getByIdentifier($identifier);

        if (!$page) {
            http_response_code(404);
            $this->render('frontend/page-detail', [
                'page' => ['title' => 'Tidak Ditemukan', 'page_identifier' => $identifier, 'html_content' => '<p>Halaman yang Anda cari tidak ditemukan.</p>']
            ]);
            return;
        }

        $this->render('frontend/page-detail', ['page' => $page]);
    }

    // ==========================================
    // AREA ADMIN (BACKEND)
    // ==========================================

    public function listAdmin() {
        $this->requireLogin();
        $pageModel = new Page();
        $pages = $pageModel->getAll();

        $this->render('backend/pages-list', [
            'pages' => $pages,
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    public function createForm() {
        $this->requireLogin();
        $this->render('backend/pages-create', [
            'csrf_token' => generateCsrfToken()
        ], true);
    }

    public function store() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            regenerateCsrfToken();
            $this->pageError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/pages/create');
        }

        $identifier = trim($_POST['identifier'] ?? '');
        $title = trim($_POST['title'] ?? '');

        if (empty($identifier)) {
            $this->pageError('Identifier tidak boleh kosong.', 'admin/pages/create');
        }

        $identifier = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $identifier));
        $identifier = trim($identifier, '-');

        $htmlContent = sanitizeHtmlContent($_POST['html_content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? null;

        $pageModel = new Page();

        if ($pageModel->getByIdentifier($identifier)) {
            regenerateCsrfToken();
            $this->pageError('Identifier sudah digunakan. Silakan gunakan yang lain.', 'admin/pages/create');
        }

        $pageModel->create($identifier, $title, $htmlContent, $adminId);

        regenerateCsrfToken();
        logInfo("Page created: identifier={$identifier}, title={$title} (admin_id: {$adminId})");

        header("Location: " . BASE_URL . "admin/pages?status=created");
        exit;
    }

    public function delete() {
        $this->requireLogin();

        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            regenerateCsrfToken();
            $this->pageError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/pages');
        }

        $identifier = $_POST['identifier'] ?? '';
        if (empty($identifier)) {
            $this->pageError('Identifier tidak valid.', 'admin/pages');
        }

        $pageModel = new Page();
        $pageModel->delete($identifier);

        regenerateCsrfToken();
        logInfo("Page deleted: identifier={$identifier} (admin_id: {$_SESSION['admin_id']})");

        header("Location: " . BASE_URL . "admin/pages?status=deleted");
        exit;
    }

    public function editForm($identifier) {
        $this->requireLogin();

        $pageModel = new Page();
        $page = $pageModel->getByIdentifier($identifier);

        if (!$page) {
            $this->pageError('Halaman dengan identifier "' . htmlspecialchars($identifier) . '" tidak ditemukan.', 'admin/pages');
        }

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
            regenerateCsrfToken();
            $this->pageError('Token CSRF tidak valid. Silakan coba lagi.', 'admin/pages/edit/' . $identifier);
        }

        $pageModel = new Page();

        $existingPage = $pageModel->getByIdentifier($identifier);
        if (!$existingPage) {
            $this->pageError('Halaman dengan identifier "' . $identifier . '" tidak ditemukan.', 'admin/pages');
        }

        $htmlContent = sanitizeHtmlContent($_POST['html_content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? null;

        $pageModel->updateContent($identifier, $htmlContent, $adminId);

        regenerateCsrfToken();
        logInfo("Page updated: identifier={$identifier} (admin_id: {$adminId})");

        $_SESSION['page_edit_flash'] = 'Konten halaman berhasil diperbarui.';
        header("Location: " . BASE_URL . "admin/pages/edit/" . $identifier);
        exit;
    }
}
