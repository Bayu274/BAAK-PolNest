<?php require_once __DIR__ . '/../layout/public_header.php'; ?>

<!-- Hero Section / Banner -->
<div class="hero-section text-center mb-5">
    <div class="container">
        <h1 class="display-5 fw-bold" style="color: #003366;">Portal Informasi BAAK</h1>
        <p class="lead text-secondary">Pusat Layanan Administrasi Akademik & Kemahasiswaan Politeknik Nest</p>
    </div>
</div>

<!-- Daftar Pengumuman & Berita -->
<div class="container mb-5">
    <h3 class="mb-4 border-bottom pb-2">Berita & Pengumuman Terbaru</h3>
    <div class="row g-4">
        
        <?php if(!empty($latestNews)): ?>
            <?php foreach($latestNews as $item): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <!-- Cek jika ada thumbnail -->
                        <?php if(!empty($item['thumbnail_image'])): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($item['thumbnail_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <!-- Gambar default jika admin tidak upload thumbnail -->
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 200px;">
                                <i class="bi bi-newspaper fs-1"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted mb-2"><i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($item['created_at'])) ?></small>
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($item['title']) ?></h5>
                            <!-- Potong teks menjadi cuplikan pendek -->
                            <p class="card-text text-secondary mb-4"><?= substr(strip_tags($item['content']), 0, 100) ?>...</p>
                            
                            <!-- Tombol baca selengkapnya ditaruh paling bawah (mt-auto) -->
                            <a href="/news?slug=<?= htmlspecialchars($item['slug']) ?>" class="btn btn-outline-primary mt-auto">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                <p>Belum ada pengumuman terbaru yang dipublikasikan.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once __DIR__ . '/../layout/public_footer.php'; ?>