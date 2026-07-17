<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold" style="color: #003366;">
            <i class="bi bi-pencil-square me-2"></i><?= isset($news) ? 'Edit Berita' : 'Tambah Berita' ?>
        </h2>
        <a href="<?= BASE_URL ?>admin/news" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>admin/news/<?= isset($news) ? 'update' : 'store' ?>" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <?php if (isset($news)): ?>
                    <input type="hidden" name="id" value="<?= (int)$news['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Judul Berita</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?= isset($news) ? htmlspecialchars($news['title']) : '' ?>"
                           placeholder="Masukkan judul berita..." required>
                </div>

                <div class="mb-3">
                    <label for="thumbnail_image" class="form-label fw-bold">Gambar Thumbnail (Opsional)</label>
                    <input type="file" class="form-control" id="thumbnail_image" name="thumbnail_image"
                           accept="image/jpeg, image/png">
                    <div class="form-text">Format yang didukung: JPG/JPEG, PNG. Maksimal 2MB.</div>

                    <?php if (isset($news) && !empty($news['thumbnail_image'])): ?>
                        <div class="mt-2">
                            <small class="text-muted">Gambar saat ini:</small><br>
                            <img src="<?= BASE_URL . ltrim(htmlspecialchars($news['thumbnail_image']), '/') ?>"
                                 alt="Thumbnail" class="img-thumbnail mt-2" style="max-height: 150px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Isi Konten</label>
                    <textarea class="form-control rich-text-editor" id="content" name="content" rows="10"
                              placeholder="Tulis isi berita di sini..."><?= isset($news) ? htmlspecialchars($news['content']) : '' ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>admin/news" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> <?= isset($news) ? 'Update Berita' : 'Simpan & Publikasikan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '.rich-text-editor',
        menubar: false,
        plugins: 'lists link image',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link'
    });
</script>