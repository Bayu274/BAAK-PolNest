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
                <img src="<?= BASE_URL . ltrim(e($news['thumbnail_image']), '/') ?>" alt="<?= e($news['title']) ?>" class="img-fluid rounded mb-4" style="max-height: 400px; object-fit: cover;">
            <?php endif; ?>

            <div class="news-content fs-5" style="line-height: 1.8;">
                <?php
                // Raw HTML output — intentional untuk konten CKEditor (rich text).
                // Konten sudah disanitasi oleh HTMLPurifier saat save di controller.
                // Defense-in-depth: sanitasi lagi saat render sebagai lapisan keamanan kedua.
                if (function_exists('sanitizeHtmlContent')) {
                    echo sanitizeHtmlContent($news['content'] ?? '');
                } else {
                    logWarning("HTMLPurifier not loaded — outputting news content with htmlspecialchars fallback.");
                    echo htmlspecialchars($news['content'] ?? '', ENT_QUOTES, 'UTF-8');
                }
                ?>
            </div>

            <hr class="mt-5 mb-4">
            <a href="<?= BASE_URL ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
        </div>
    </div>
</main>