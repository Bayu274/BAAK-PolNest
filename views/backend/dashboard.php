<main class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold" style="color: #003366;">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
        </h2>
        <span class="text-muted small">Selamat datang, <?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="bi bi-newspaper fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Berita</div>
                        <div class="fs-3 fw-bold text-dark"><?= (int)$stats['news'] ?></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>admin/news" class="text-decoration-none small">Lihat semua <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="bi bi-file-earmark-pdf fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">File Aktif</div>
                        <div class="fs-3 fw-bold text-dark"><?= (int)$stats['files'] ?></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>admin/files" class="text-decoration-none small">Kelola file <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Data Dosen</div>
                        <div class="fs-3 fw-bold text-dark"><?= (int)$stats['advisors'] ?></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>admin/import-csv" class="text-decoration-none small">Import CSV <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Halaman</div>
                        <div class="fs-3 fw-bold text-dark"><?= (int)$stats['pages'] ?></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="<?= BASE_URL ?>admin/pages" class="text-decoration-none small">Kelola halaman <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Berita Terbaru</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentNews)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentNews as $item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <div class="fw-semibold"><?= e($item['title']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d M Y, H:i', strtotime($item['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Belum ada berita.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-lightning me-2"></i>Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>admin/news/create" class="btn btn-outline-primary text-start">
                            <i class="bi bi-plus-lg me-2"></i>Tulis Berita Baru
                        </a>
                        <a href="<?= BASE_URL ?>admin/pages/create" class="btn btn-outline-success text-start">
                            <i class="bi bi-plus-lg me-2"></i>Buat Halaman Baru
                        </a>
                        <a href="<?= BASE_URL ?>admin/files" class="btn btn-outline-info text-start">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload Dokumen
                        </a>
                        <a href="<?= BASE_URL ?>admin/import-csv" class="btn btn-outline-warning text-start">
                            <i class="bi bi-upload me-2"></i>Import Data CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
