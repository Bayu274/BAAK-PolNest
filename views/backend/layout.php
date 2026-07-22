<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - BAAK Politeknik Nest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <nav class="bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
            <h5 class="mb-4">BAAK Admin</h5>
            <ul class="nav flex-column">
                <li class="nav-item mb-2"><a href="<?= BASE_URL ?>dashboard" class="nav-link text-white">Dashboard</a></li>
                <li class="nav-item mb-2"><a href="<?= BASE_URL ?>admin/news" class="nav-link text-white">Berita</a></li>
                <!-- Link di bawah ini sudah disesuaikan ke daftar halaman -->
                <li class="nav-item mb-2"><a href="<?= BASE_URL ?>admin/pages" class="nav-link text-white">Konten Halaman</a></li>
                <li class="nav-item mb-2"><a href="<?= BASE_URL ?>admin/files" class="nav-link text-white">Berkas Unduhan</a></li>
                <li class="nav-item mb-2"><a href="<?= BASE_URL ?>admin/import-csv" class="nav-link text-white">Data Pembimbing</a></li>
                <li class="nav-item mt-4"><a href="<?= BASE_URL ?>logout" class="nav-link text-warning">Logout</a></li>
            </ul>
        </nav>
        <main class="flex-grow-1 p-4">
            <?php
            // Area dinamis — $content berisi HTML hasil render view backend (string), bukan path file
            if (isset($content) && $content !== null) {
                echo $content;
            } else {
                echo "<p>Selamat datang di Dashboard Admin.</p>";
            }
            ?>
        </main>
    </div>
</body>
</html>