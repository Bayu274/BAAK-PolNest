<div class="container-fluid mt-4">
    <?php if (isset($_GET['status']) && $_GET['status'] === 'imported'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> Data pembimbing berhasil diimpor dan diperbarui. Cek tabel di bawah untuk memastikan datanya sudah benar.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color: #003366;">
                <i class="bi bi-people me-2"></i>Data Pembimbing
            </h4>
            <span class="text-muted small">
                Ditampilkan langsung dari database (real-time). Data tercatat: <?= (int) $stats['total'] ?> baris.
            </span>
        </div>
        <a href="<?= BASE_URL ?>admin/import-csv" class="btn btn-success px-4 btn-submit">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV
        </a>
    </div>

    <p class="text-muted small mb-4">
        Dikonversi ke tabel di bawah ini bersumber dari <code>student_advisors</code> di database, bukan dari file CSV.
        Gunakan tombol <strong>Import CSV</strong> di pojok kanan atas untuk memperbarui atau mengganti seluruh data.
    </p>

    <?php
    $typeBadges = [
        'Wali'   => ['bg-primary',   'bi-person-badge'],
        'Magang' => ['bg-info',      'bi-briefcase'],
        'TA'     => ['bg-success',   'bi-journal-bookmark'],
    ];
    if (!isset($filter)) { $filter = ['keyword' => '', 'advisor_type' => '']; }
    ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Total Data</div>
                    <div class="fs-3 fw-bold"><?= (int) $stats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Total Mahasiswa</div>
                    <div class="fs-3 fw-bold"><?= (int) $stats['students'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Dosen Pembimbing</div>
                    <div class="fs-3 fw-bold"><?= (int) $stats['advisors'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Terakhir Diperbarui</div>
                    <div class="fs-6 fw-bold">
                        <?= $stats['last_import'] ? e(date('d M Y, H:i', strtotime($stats['last_import']))) : 'Belum ada data' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="<?= BASE_URL ?>admin/data-pembimbing" class="row g-2 align-items-end mb-3">
        <div class="col-md-5">
            <label for="q" class="form-label small mb-1 fw-semibold">Cari NIM / Nama Mahasiswa / Dosen</label>
            <input
                type="text"
                class="form-control"
                id="q"
                name="q"
                value="<?= e($filter['keyword']) ?>"
                placeholder="misal: 21010001 atau nama dosen"
                maxlength="100"
            >
        </div>
        <div class="col-md-3">
            <label for="type" class="form-label small mb-1 fw-semibold">Jenis Pembimbing</label>
            <select class="form-select" id="type" name="type">
                <option value="">Semua Jenis</option>
                <?php foreach (['Wali', 'Magang', 'TA'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($filter['advisor_type'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-submit">
                <i class="bi bi-search me-1"></i>Cari
            </button>
            <a href="<?= BASE_URL ?>admin/data-pembimbing" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </a>
        </div>
    </form>

    <?php if ($total === 0): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                <?php if ($filter['keyword'] !== '' || $filter['advisor_type'] !== ''): ?>
                    Tidak ditemukan data yang cocok dengan pencarian Anda.
                <?php else: ?>
                    Belum ada data pembimbing. Klik tombol <strong>Import CSV</strong> untuk memuat data.
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">
                    <i class="bi bi-table me-1"></i>
                    <?php
                    $from = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
                    $to   = min($total, $page * $perPage);
                    if ($filter['keyword'] !== '' || $filter['advisor_type'] !== '') {
                        echo "Hasil pencarian: {$total} baris";
                    } else {
                        echo "Menampilkan {$from} - {$to} dari {$total} baris";
                    }
                    ?>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dataTableAdvisor">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">No</th>
                            <th style="width: 140px;">NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Dosen Pembimbing</th>
                            <th class="text-center" style="width: 120px;">Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = ($page - 1) * $perPage; foreach ($records as $row): $no++; ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no ?></td>
                                <td><code><?= e($row['nim']) ?></code></td>
                                <td><?= e($row['student_name']) ?></td>
                                <td><?= e($row['advisor_name']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $badge = $typeBadges[$row['advisor_type']] ?? ['bg-secondary', 'bi-question-circle'];
                                    ?>
                                    <span class="badge <?= e($badge[0]) ?> text-white">
                                        <i class="bi <?= e($badge[1]) ?> me-1"></i><?= e($row['advisor_type']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php if ($totalPages > 1): ?>
            <?php
            $qs = function (int $p) use ($filter) {
                $url = BASE_URL . 'admin/data-pembimbing?page=' . $p;
                if ($filter['keyword'] !== '') { $url .= '&q=' . urlencode($filter['keyword']); }
                if ($filter['advisor_type'] !== '') { $url .= '&type=' . $filter['advisor_type']; }
                return $url;
            };
            ?>
            <nav aria-label="Navigasi halaman">
                <ul class="pagination pagination-sm justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($qs(max(1, $page - 1))) ?>">&laquo;</a>
                    </li>
                    <?php
                    $from = max(1, $page - 2);
                    $to   = min($totalPages, $page + 2);
                    for ($i = $from; $i <= $to; $i++):
                        ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e($qs($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e($qs(min($totalPages, $page + 1))) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>