<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BAAK Politeknik Nest</title>
    
    <!-- Load Google Fonts (Montserrat & Roboto) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Load Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Load Design System BAAK Nest -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/design-system.css">
    
    <style>
        .sidebar-nest { width: 250px; min-height: 100vh; }
        .sidebar-nest .nav-link { border-radius: 0.375rem; transition: all 0.2s ease-in-out; }
        .sidebar-nest .nav-link:hover { background-color: rgba(255, 255, 255, 0.12); }
        
        /* Kelas warna gradien BAAK Nest */
        .bg-gradient-nest {
            background: linear-gradient(135deg, #f88a4d 0%, #f96d80 100%) !important;
        }

        /* Style khusus untuk tab yang sedang aktif */
        .sidebar-nest .nav-link.active {
            background-color: #ffffff !important;
            color: #f96d80 !important;
            font-weight: 700;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Ubah warna ikon di tab aktif menjadi pink */
        .sidebar-nest .nav-link.active i {
            color: #f96d80 !important;
        }
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
    
    // Logika untuk mendeteksi URL saat ini
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    ?>
    <div class="d-flex">
        <!-- Sidebar desktop (>= lg) -->
        <nav class="bg-gradient-nest text-white p-3 d-none d-lg-block flex-shrink-0 sidebar-nest">
            <h5 class="mb-4 fw-bold">BAAK Admin</h5>
            <ul class="nav flex-column">
                <?php foreach ($sidebarItems as $item): 
                    $itemPath = parse_url($item['href'], PHP_URL_PATH);
                    // Cek apakah URL saat ini mengandung path menu ini
                    $isActive = ($itemPath != '/' && strpos($currentPath, $itemPath) !== false) ? 'active' : '';
                    $textClass = $isActive ? '' : 'text-white';
                ?>
                    <li class="nav-item mb-2">
                        <a href="<?= e($item['href']) ?>" class="nav-link <?= $isActive ?> <?= $textClass ?>">
                            <i class="bi bi-<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item mt-4">
                    <form action="<?= BASE_URL ?>logout" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                        <button type="submit" class="nav-link text-warning btn btn-link p-0 fw-bold w-100 text-start ps-3">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Offcanvas sidebar mobile (< lg) -->
        <div class="offcanvas offcanvas-start bg-gradient-nest text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
            <div class="offcanvas-header border-bottom border-light border-opacity-25">
                <h5 class="offcanvas-title text-white fw-bold" id="sidebarOffcanvasLabel">BAAK Admin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="offcanvas-body pt-3">
                <ul class="nav flex-column sidebar-nest" style="width: 100%;">
                    <?php foreach ($sidebarItems as $item): 
                        $itemPath = parse_url($item['href'], PHP_URL_PATH);
                        $isActive = ($itemPath != '/' && strpos($currentPath, $itemPath) !== false) ? 'active' : '';
                        $textClass = $isActive ? '' : 'text-white';
                    ?>
                        <li class="nav-item mb-2">
                            <a href="<?= e($item['href']) ?>" class="nav-link <?= $isActive ?> <?= $textClass ?>" data-bs-dismiss="offcanvas">
                                <i class="bi bi-<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-item mt-4">
                        <form action="<?= BASE_URL ?>logout" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                            <button type="submit" class="nav-link text-warning btn btn-link p-0 fw-bold w-100 text-start ps-3">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <main class="flex-grow-1 min-vh-100 bg-light">
            <!-- Topbar mobile: tombol buka menu -->
            <nav class="navbar navbar-dark bg-gradient-nest d-lg-none px-3 shadow-sm">
                <span class="fw-bold text-white">BAAK Admin</span>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Buka menu navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </nav>
            <div class="p-4">
                <?php
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