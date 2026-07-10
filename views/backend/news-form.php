<main class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= isset($news) ? 'Edit Berita' : 'Tambah Berita / Pengumuman Baru' ?></h5>
        </div>
        <div class="card-body">
            <form action="<?= isset($news) ? '/admin/news/update' : '/admin/news/store' ?>" method="POST" enctype="multipart/form-data">
                
                <?php if(isset($news)): ?>
                    <input type="hidden" name="id" value="<?= $news['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Judul Berita</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Masukkan judul berita..." 
                           value="<?= isset($news) ? htmlspecialchars($news['title']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label for="thumbnail" class="form-label fw-bold">Gambar Thumbnail</label>
                    <input class="form-control" type="file" id="thumbnail" name="thumbnail" accept="image/jpeg, image/png">
                    <div class="form-text">
                        Format: JPG, PNG. Maksimal 2MB. 
                        <?= isset($news) ? '<br><span class="text-danger">*Kosongkan jika tidak ingin mengganti gambar.</span>' : '' ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Isi Konten</label>
                    <textarea class="form-control rich-text-editor" id="content" name="content" rows="10"><?= isset($news) ? htmlspecialchars($news['content']) : '' ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/news" class="btn btn-secondary">Batal</a>
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
        plugins: 'lists link',
        toolbar: 'undo redo | bold italic | bullist numlist | link'
    });
</script>