<main>
    <div class="container mt-4">
        <div class="hero-nest p-4 p-md-5 mb-5">
            <span class="hero-nest-badge mb-3">
                <i class="bi bi-patch-check-fill"></i> Portal Resmi BAAK
            </span>
            <h1 class="mb-3">Portal Informasi BAAK</h1>
            <p class="mb-4 col-lg-8">
                Selamat datang di layanan publik Biro Administrasi &amp; Akademik Kampus Politeknik Nest.
                Temukan pengumuman terbaru, kalender akademik, dan unduh dokumen keperluan perkuliahan dengan mudah.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>pencarian-dosen" class="btn btn-light btn-lg">
                    <i class="bi bi-search me-1"></i> Cari Dosen Pembimbing
                </a>
                <a href="<?= BASE_URL ?>jadwal" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-calendar3 me-1"></i> Jadwal &amp; Pedoman
                </a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <h3 class="section-title-nest mb-0">Berita &amp; Pengumuman Terbaru</h3>
                    <a href="<?= BASE_URL ?>berita" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <?php if (!empty($latestNews)): ?>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($latestNews as $item): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <?php if (!empty($item['thumbnail_image'])): ?>
                                        <img src="<?= BASE_URL . ltrim(e($item['thumbnail_image']), '/') ?>" alt="<?= e($item['title']) ?>" class="card-img-top" style="height: 170px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 170px;">
                                            <i class="bi bi-newspaper fs-1"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title fw-bold"><?= e($item['title']) ?></h6>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($item['created_at'])) ?>
                                        </p>
                                        <p class="card-text text-secondary small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= e(substr(strip_tags($item['content']), 0, 100)) ?>...
                                        </p>
                                        <a href="<?= BASE_URL ?>berita/<?= e($item['slug']) ?>" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5 border rounded-3">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        <p class="mb-0">Belum ada pengumuman terbaru yang dipublikasikan.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <h3 class="section-title-nest">Akses Cepat</h3>
                <div class="d-flex flex-column gap-3">
                    <a href="<?= BASE_URL ?>pencarian-dosen" class="card text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-search fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Cari Dosen Pembimbing</div>
                                <small class="text-muted">Cek pembimbing berdasarkan NIM</small>
                            </div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>jadwal" class="card text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-calendar3 fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Jadwal &amp; Pedoman</div>
                                <small class="text-muted">Kalender akademik, formulir, SOP</small>
                            </div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>layanan" class="card text-decoration-none">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-briefcase fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Layanan BAAK</div>
                                <small class="text-muted">Info layanan administrasi akademik</small>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="card mt-4 border-0" style="background-color: var(--nest-primary); color: #fff;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="bi bi-clock-fill me-1"></i> Jam Layanan Loket</h6>
                        <p class="small mb-1 opacity-75">Senin – Jumat</p>
                        <p class="small mb-0 fw-semibold">08.00 – 15.00 WIB</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>