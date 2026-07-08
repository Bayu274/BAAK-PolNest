<main class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Tambah Berita / Pengumuman Baru</h5>
        </div>
        <div class="card-body">
            <form action="/admin/news/store" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Judul Berita</label>
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