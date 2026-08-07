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
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($newsList as $item): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($item['thumbnail_image'])): ?>
                            <img src="<?= BASE_URL . ltrim(e($item['thumbnail_image']), '/') ?>" alt="<?= e($item['title']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 200px;">
                                <i class="bi bi-newspaper fs-1"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= e($item['title']) ?></h5>
                            <p class="card-text text-muted small mb-3">
                                <i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($item['created_at'])) ?>
                            </p>
                            <p class="card-text text-secondary" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= e(substr(strip_tags($item['content']), 0, 150)) ?>...
                            </p>
                            <a href="<?= BASE_URL ?>berita/<?= e($item['slug']) ?>" class="btn btn-outline-primary mt-auto">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            <p class="mb-0"><?= !empty($searchQuery) ? 'Tidak ada berita yang cocok dengan kata kunci "' . e($searchQuery) . '".' : 'Belum ada berita yang dipublikasikan.' ?></p>
        </div>
    <?php endif; ?>
</main>
