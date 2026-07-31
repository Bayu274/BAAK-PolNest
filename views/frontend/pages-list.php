<main class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Layanan BAAK</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="fw-bold mb-1">Layanan BAAK</h1>
        <p class="text-secondary mb-0">Prosedur operasional standar (SOP) layanan akademik BAAK Politeknik Nest.</p>
    </div>

    <?php if (!empty($pages)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($pages as $page): ?>
                <div class="col">
                    <a href="<?= BASE_URL ?>halaman/<?= e($page['page_identifier']) ?>" class="text-decoration-none">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body d-flex align-items-start p-4">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; flex-shrink: 0;">
                                    <i class="bi bi-file-earmark-text-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-1"><?= e($page['title'] ?: $page['page_identifier']) ?></h5>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i> Diperbarui: <?= date('d M Y', strtotime($page['last_updated'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            <p class="mb-0">Belum ada layanan yang dipublikasikan.</p>
        </div>
    <?php endif; ?>
</main>
