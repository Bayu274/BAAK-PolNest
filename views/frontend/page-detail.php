<main class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($page['title'] ?: $page['page_identifier']) ?></li>
                </ol>
            </nav>

            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h1 class="fw-bold mb-4 border-bottom border-2 border-primary pb-3">
                        <?= e($page['title'] ?: $page['page_identifier']) ?>
                    </h1>

                    <div class="page-content text-secondary" style="line-height: 1.8; font-size: 1.1rem;">
                        <?php
                        if (function_exists('sanitizeHtmlContent')) {
                            echo sanitizeHtmlContent($page['html_content'] ?? '');
                        } else {
                            echo htmlspecialchars($page['html_content'] ?? '', ENT_QUOTES, 'UTF-8');
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-4 d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                <div>
                    <strong>Butuh bantuan lebih lanjut?</strong><br>
                    Silakan kunjungi loket pelayanan fisik BAAK Politeknik Nest pada jam kerja (Senin - Jumat, 08.00 - 15.00 WIB).
                </div>
            </div>
        </div>
    </div>
</main>
