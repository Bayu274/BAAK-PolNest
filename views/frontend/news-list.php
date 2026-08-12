<main class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Berita</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
        <div>
            <h1 class="fw-bold mb-1">Berita & Pengumuman</h1>
            <p class="text-secondary mb-0">Seluruh informasi dan pengumuman terbaru dari BAAK Politeknik Nest.</p>
        </div>
        <form action="<?= BASE_URL ?>berita" method="GET" class="d-flex" role="search">
            <input type="search" name="q" class="form-control me-2" placeholder="Cari berita..." value="<?= e($searchQuery ?? '') ?>" aria-label="Cari berita">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <?php if (!empty($newsList)): ?>
        <div class="list-group">
            <?php foreach ($newsList as $item): ?>
                <a href="<?= BASE_URL ?>berita/<?= e($item['slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">
                    <?php if (!empty($item['thumbnail_image'])): ?>
                        <img src="<?= BASE_URL . ltrim(e($item['thumbnail_image']), '/') ?>" alt="<?= e($item['title']) ?>" class="rounded flex-shrink-0" style="width: 90px; height: 70px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 90px; height: 70px;">
                            <i class="bi bi-newspaper"></i>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold text-dark"><?= e($item['title']) ?></div>
                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($item['created_at'])) ?>
                        </small>
                        <p class="text-secondary small mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e(substr(strip_tags($item['content']), 0, 150)) ?>...
                        </p>
                    </div>
                    <i class="bi bi-chevron-right text-muted align-self-center flex-shrink-0"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            <p class="mb-0"><?= !empty($searchQuery) ? 'Tidak ada berita yang cocok dengan kata kunci "' . e($searchQuery) . '".' : 'Belum ada berita yang dipublikasikan.' ?></p>
        </div>
    <?php endif; ?>
</main> 