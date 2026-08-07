<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BAAK Politeknik Nest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .sidebar-nest { width: 250px; min-height: 100vh; }
        .sidebar-nest .nav-link { border-radius: 0.375rem; }
        .sidebar-nest .nav-link:hover { background-color: rgba(255, 255, 255, 0.12); }
        .sidebar-nest .nav-link.active { background-color: rgba(7, 76, 132, 0.65); font-weight: 600; }
    </style>
</head>
<body>
    <?php
    $sidebarItems = [
        ['href' => BASE_URL . 'dashboard',        'icon' => 'speedometer2',       'label' => 'Dashboard'],
        ['href' => BASE_URL . 'admin/news',       'icon' => 'newspaper',          'label' => 'Berita'],
        ['href' => BASE_URL . 'admin/pages',      'icon' => 'file-earmark-text',  'label' => 'Konten Halaman'],
        ['href' => BASE_URL . 'admin/files',      'icon' => 'folder2-open',       'label' => 'Berkas Unduhan'],
['href' => BASE_URL . 'admin/data-pembimbing', 'icon' => 'people', 'label' => 'Data Pembimbing'],
    ];
    ?>
    <div class="d-flex">
        <!-- Sidebar desktop (>= lg) -->
        <nav class="bg-dark text-white p-3 d-none d-lg-block flex-shrink-0 sidebar-nest">
            <h5 class="mb-4">BAAK Admin</h5>
            <ul class="nav flex-column">
                <?php foreach ($sidebarItems as $item): ?>
                    <li class="nav-item mb-2">
                        <a href="<?= e($item['href']) ?>" class="nav-link text-white">
                            <i class="bi bi-<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item mt-4">
                    <form action="<?= BASE_URL ?>logout" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                        <button type="submit" class="nav-link text-warning btn btn-link p-0">Logout</button>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Offcanvas sidebar mobile (< lg) -->
        <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title text-white" id="sidebarOffcanvasLabel">BAAK Admin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="offcanvas-body pt-0">
                <ul class="nav flex-column">
                    <?php foreach ($sidebarItems as $item): ?>
                        <li class="nav-item mb-2">
                            <a href="<?= e($item['href']) ?>" class="nav-link text-white" data-bs-dismiss="offcanvas">
                                <i class="bi bi-<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-item mt-4">
                        <form action="<?= BASE_URL ?>logout" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                            <button type="submit" class="nav-link text-warning btn btn-link p-0">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <main class="flex-grow-1 min-vh-100">
            <!-- Topbar mobile: tombol buka menu -->
            <nav class="navbar navbar-dark bg-dark d-lg-none px-3">
                <span class="fw-bold text-white">BAAK Admin</span>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Buka menu navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </nav>
            <div class="p-4">
                <?php
                // Area dinamis — $content berisi HTML hasil render view backend (string), bukan path file
                if (isset($content) && $content !== null) {
                    echo $content;
                } else {
                    echo "<p>Selamat datang di Dashboard Admin.</p>";
                }
                ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?= generateCspNonce() ?>">
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('.btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
            }
        });
    });
    </script>
</body>
</html>