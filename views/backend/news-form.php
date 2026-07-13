<<<<<<< HEAD
<main class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Tambah Berita / Pengumuman Baru</h5>
        </div>
        <div class="card-body">
            <form action="/admin/news/store" method="POST" enctype="multipart/form-data">
=======
<?php require_once __DIR__ . '/../layout/public_header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold" style="color: #003366;">
            <i class="bi bi-pencil-square me-2"></i><?= isset($news) ? 'Edit Berita' : 'Tambah Berita' ?>
        </h2>
        <a href="/admin/news" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="<?= isset($news) ? '/admin/news/update' : '/admin/news/store' ?>" method="POST" enctype="multipart/form-data">
>>>>>>> 673d63530e97d1525bf0093638aea7f29cd088d4
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Judul Berita</label>
<<<<<<< HEAD
                    <input type="text" class="form-control" id="title" name="title" placeholder="Masukkan judul berita..." required>
                </div>

                <div class="mb-3">
                    <label for="thumbnail" class="form-label fw-bold">Gambar Thumbnail (Opsional)</label>
                    <input class="form-control" type="file" id="thumbnail" name="thumbnail" accept="image/jpeg, image/png">
                    <div class="form-text">Format yang didukung: JPG/JPEG, PNG. Maksimal 2MB.</div>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Isi Konten</label>
                    <textarea class="form-control rich-text-editor" id="content" name="content" rows="10" placeholder="Tulis isi berita di sini..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/news" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Simpan & Publikasikan
=======
                    <input type="text" class="form-control" id="title" name="title" value="<?= isset($news) ? htmlspecialchars($news['title']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label for="thumbnail_image" class="form-label fw-bold">Gambar Thumbnail (Opsional)</label>
                    <input type="file" class="form-control" id="thumbnail_image" name="thumbnail_image" accept="image/*">
                    
                    <?php if(isset($news) && !empty($news['thumbnail_image'])): ?>
                        <div class="mt-2">
                            <small class="text-muted">Gambar saat ini:</small><br>
                            <img src="/assets/uploads/<?= htmlspecialchars($news['thumbnail_image']) ?>" alt="Thumbnail" class="img-thumbnail mt-2" style="max-height: 150px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Isi Berita</label>
                    <textarea class="form-control" id="content" name="content" rows="10"><?= isset($news) ? htmlspecialchars($news['content']) : '' ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> <?= isset($news) ? 'Update Berita' : 'Simpan Berita' ?>
>>>>>>> 673d63530e97d1525bf0093638aea7f29cd088d4
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<<<<<<< HEAD
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '.rich-text-editor',
        menubar: false,
        plugins: 'lists link image',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link'
    });
</script>
=======
<?php require_once __DIR__ . '/../layout/public_footer.php'; ?>
>>>>>>> 673d63530e97d1525bf0093638aea7f29cd088d4
