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
                <p class="text-muted">Dokumen resmi operasional akademik. Klik dokumen yang diinginkan untuk langsung mengunduhnya.</p>
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
                <div class="alert alert-info border-0 shadow-sm">
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
                <div class="row g-3 mb-4">
                    <?php foreach ($grouped as $category => $categoryFiles): ?>
                        <div class="col-12">
                            <h5 class="fw-bold text-dark mt-2 mb-2">
                                <i class="bi bi-folder2-open me-2 text-primary"></i><?= e($categoryLabels[$category] ?? ucwords(str_replace('_', ' ', $category))) ?>
                            </h5>
                        </div>
                        <?php foreach ($categoryFiles as $file): ?>
                            <div class="col-md-6">
                                <a href="<?= BASE_URL ?>files/download/<?= (int) $file['id'] ?>" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm h-100 rounded-3">
                                        <div class="card-body d-flex align-items-center p-3">
                                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; flex-shrink: 0;">
                                                <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="fw-semibold text-dark text-truncate"><?= e($file['file_name']) ?></div>
                                                <small class="text-muted">
                                                    <?= e(date('d M Y', strtotime($file['uploaded_at']))) ?>
                                                </small>
                                            </div>
                                            <span class="btn btn-danger btn-sm ms-2 flex-shrink-0">
                                                <i class="bi bi-download me-1"></i>Unduh
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <a href="<?= e(RPS_URL) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 rounded-3">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; flex-shrink: 0;">
                                    <i class="bi bi-box-arrow-up-right fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Situs RPS (Rencana Pembelajaran)</div>
                                    <small class="text-muted">Katalog RPS seluruh mata kuliah — tautan eksternal</small>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 bg-white shadow-sm h-100 rounded-3">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <small class="fw-semibold text-dark">Email</small><br>
                                <small class="text-muted">baak@politekniknest.ac.id</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-white shadow-sm h-100 rounded-3">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <small class="fw-semibold text-dark">Lokasi</small><br>
                                <small class="text-muted">Gedung Rektorat Lantai 1</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>