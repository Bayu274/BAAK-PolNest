<main class="container mt-5">
    <div class="hero-nest p-4 p-md-5 mb-5">
        <div class="container-fluid px-0 py-2">
            <span class="hero-nest-badge mb-3"><i class="bi bi-megaphone-fill"></i> Portal Resmi BAAK</span>
            <h1 class="display-5 fw-bold mt-3">Portal Informasi BAAK</h1>
            <p class="col-md-8 fs-5 mt-3 mb-4">
                Selamat datang di layanan publik Biro Administrasi & Akademik Kampus Politeknik Nest.
                Temukan pengumuman terbaru, kalender akademik, dan unduh dokumen keperluan perkuliahan dengan mudah.
            </p>
            <a href="<?= BASE_URL ?>pencarian-dosen" class="btn btn-light btn-lg">
                <i class="bi bi-search"></i> Cari Dosen Pembimbing
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title-nest mb-0">Berita & Pengumuman Terbaru</h3>
            <a href="<?= BASE_URL ?>berita" class="btn btn-outline-primary btn-sm">
                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (!empty($latestNews)): ?>
            <?php foreach ($latestNews as $item): ?>
                <div class="col-md-4 mb-4">
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
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                <p>Belum ada pengumuman terbaru yang dipublikasikan.</p>
            </div>
        <?php endif; ?>
    </div>
</main>