<?php require_once __DIR__ . '/../layout/public_header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <!-- Menggunakan col-lg-8 agar konten tidak terlalu melebar dan nyaman dibaca -->
        <div class="col-lg-8">
            
            <!-- Breadcrumb Navigasi -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengumuman</li>
                </ol>
            </nav>

            <!-- Judul Berita -->
            <h1 class="fw-bold mb-3"><?= htmlspecialchars($news['title']) ?></h1>
            <div class="text-muted mb-4 pb-3 border-bottom">
                <i class="bi bi-calendar3 me-1"></i> Dipublikasikan pada: <?= date('d F Y, H:i', strtotime($news['created_at'])) ?>
            </div>

            <!-- Gambar Utama / Thumbnail -->
            <?php if(!empty($news['thumbnail_image'])): ?>
                <img src="/assets/uploads/<?= htmlspecialchars($news['thumbnail_image']) ?>" class="img-fluid rounded shadow-sm mb-4 w-100" alt="<?= htmlspecialchars($news['title']) ?>" style="max-height: 450px; object-fit: cover;">
            <?php endif; ?>

            <!-- Isi Konten dari Rich Text Editor -->
            <div class="news-content fs-5" style="line-height: 1.8;">
                <?= $news['content'] ?> 
            </div>
            
            <!-- Tombol Kembali -->
            <hr class="mt-5 mb-4">
            <a href="/" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/public_footer.php'; ?>