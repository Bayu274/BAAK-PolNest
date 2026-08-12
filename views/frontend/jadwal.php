<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none text-muted">Beranda</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Jadwal & Pedoman</li>
                </ol>
            </nav>

            <div class="mb-4">
                <h1 class="h3 fw-bold text-dark mb-1">Jadwal & Pedoman BAAK</h1>
                <p class="text-muted">Dokumen resmi operasional akademik. Klik dokumen untuk langsung mengunduhnya.</p>
            </div>

            <?php
            $files = $files ?? [];
            $categoryLabels = [
                'jadwal_kuliah'   => 'Jadwal Kuliah',
                'kalender_akademik' => 'Kalender Akademik',
                'formulir_krs'    => 'Formulir KRS',
                'sop_dokumen'     => 'SOP & Pedoman',
                'panduan_ta'      => 'Panduan TA',
            ];

            if (empty($files)): ?>
                <div class="alert alert-info border-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Belum ada dokumen yang tersedia untuk diunduh. Silakan kembali lagi nanti.
                </div>
            <?php else: ?>
                <?php
                $grouped = [];
                foreach ($files as $file) {
                    $grouped[$file['file_category']][] = $file;
                }
                ?>
                <?php foreach ($grouped as $category => $categoryFiles): ?>
                    <h3 class="section-title-nest">
                        <?= e($categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category))) ?>
                    </h3>
                    <div class="list-group mb-4">
                        <?php foreach ($categoryFiles as $file): ?>
                            <a href="<?= BASE_URL ?>files/download/<?= (int) $file['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-3"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark"><?= e($file['file_name']) ?></div>
                                    <small class="text-muted"><?= e(date('d M Y', strtotime($file['uploaded_at']))) ?></small>
                                </div>
                                <i class="bi bi-download text-primary fs-5"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3 class="section-title-nest">Tautan Lainnya</h3>
            <div class="list-group mb-4">
                <a href="<?= e(RPS_URL) ?>" target="_blank" rel="noopener noreferrer" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                    <i class="bi bi-box-arrow-up-right text-primary fs-4 me-3"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark">Situs RPS (Rencana Pembelajaran)</div>
                        <small class="text-muted">Katalog RPS seluruh mata kuliah — tautan eksternal</small>
                    </div>
                    <i class="bi bi-arrow-up-right fs-5 text-muted"></i>
                </a>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <small class="fw-semibold text-dark d-block">Email</small>
                                <small class="text-muted">baak@politekniknest.ac.id</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <small class="fw-semibold text-dark d-block">Lokasi</small>
                                <small class="text-muted">Gedung Rektorat Lantai 1</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>