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
        <div class="list-group">
            <?php foreach ($pages as $page): ?>
                <a href="<?= BASE_URL ?>halaman/<?= e($page['page_identifier']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                    <i class="bi bi-file-earmark-text-fill text-primary fs-4"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark"><?= e($page['title'] ?: $page['page_identifier']) ?></div>
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i> Diperbarui: <?= date('d M Y', strtotime($page['last_updated'])) ?>
                        </small>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            <p class="mb-0">Belum ada layanan yang dipublikasikan.</p>
        </div>
    <?php endif; ?>
</main>