<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengumuman</li>
                </ol>
            </nav>

            <h1 class="fw-bold mb-3"><?= htmlspecialchars($news['title']) ?></h1>
            <div class="text-muted mb-4 pb-3 border-bottom">
                <i class="bi bi-calendar3 me-1"></i> Dipublikasikan pada: <?= date('d F Y, H:i', strtotime($news['created_at'])) ?>
            </div>

            <?php if (!empty($news['thumbnail_image'])): ?>
                <img src="<?= BASE_URL ?>storage/uploads/<?= htmlspecialchars($news['thumbnail_image']) ?>" class="img-fluid rounded shadow-sm mb-4 w-100" alt="<?= htmlspecialchars($news['title']) ?>" style="max-height: 450px; object-fit: cover;">
            <?php endif; ?>

            <div class="news-content fs-5" style="line-height: 1.8;">
                <?= $news['content'] ?>
            </div>

            <hr class="mt-5 mb-4">
            <a href="<?= BASE_URL ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </div>
</main>