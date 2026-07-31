<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'BAAK Politeknik Nest') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/design-system.css" rel="stylesheet">
</head>
<body>
    <div class="topbar-nest d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center py-1">
            <small><i class="bi bi-geo-alt-fill me-1"></i>Jl. Raya Telukan - Cuplik Km.1, Grogol, Sukoharjo</small>
            <small><i class="bi bi-envelope-fill me-1"></i> baak@politekniknest.ac.id</small>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-nest">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">BAAK Politeknik Nest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="<?= BASE_URL ?>">Beranda</a>
                    <a class="nav-link" href="<?= BASE_URL ?>berita">Berita</a>
                    <a class="nav-link" href="<?= BASE_URL ?>layanan">Layanan BAAK</a>
                    <a class="nav-link" href="<?= BASE_URL ?>jadwal">Jadwal & Pedoman</a>
                    <a class="nav-link" href="<?= BASE_URL ?>pencarian-dosen">Cari Dosen</a>
                </div>
            </div>
        </div>
    </nav>

    <?= $content ?>

    <footer class="footer-nest text-white py-4 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Navigasi</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1"><a href="<?= BASE_URL ?>" class="text-white-50 text-decoration-none">Beranda</a></li>
                        <li class="mb-1"><a href="<?= BASE_URL ?>berita" class="text-white-50 text-decoration-none">Berita & Pengumuman</a></li>
                        <li class="mb-1"><a href="<?= BASE_URL ?>layanan" class="text-white-50 text-decoration-none">Layanan BAAK</a></li>
                        <li class="mb-1"><a href="<?= BASE_URL ?>jadwal" class="text-white-50 text-decoration-none">Jadwal & Pedoman</a></li>
                        <li class="mb-1"><a href="<?= BASE_URL ?>pencarian-dosen" class="text-white-50 text-decoration-none">Cari Dosen Pembimbing</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Kontak BAAK</h6>
                    <ul class="list-unstyled mb-0 text-white-50">
                        <li class="mb-1"><i class="bi bi-envelope-fill me-2"></i>baak@politekniknest.ac.id</li>
                        <li class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i>Gedung Rektorat Lantai 1</li>
                        <li class="mb-1"><i class="bi bi-clock-fill me-2"></i>Senin - Jumat, 08.00 - 15.00 WIB</li>
                    </ul>
                </div>
                <div class="col-md-4 text-md-end">
                    <h6 class="fw-bold mb-3">Politeknik Nest</h6>
                    <p class="text-white-50 small mb-0">Biro Administrasi & Akademik Kampus</p>
                    <p class="text-white-50 small">Sukoharjo, Jawa Tengah</p>
                </div>
            </div>
            <hr class="border-secondary mt-4 mb-3">
            <div class="text-center">
                <small class="text-white-50">&copy; <?= date('Y') ?> BAAK Politeknik Nest. Hak Cipta Dilindungi.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
