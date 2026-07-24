<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold" style="color: #003366;">Tambah Halaman Baru</h3>
        <a href="<?= BASE_URL ?>admin/pages" class="btn btn-secondary">
            Kembali ke Daftar
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/pages/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Judul Halaman</label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="Contoh: Visi dan Misi Kampus">
                </div>

                <div class="mb-3">
                    <label for="identifier" class="form-label fw-semibold">Identifier (URL)</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" required placeholder="Contoh: visi-misi">
                    <small class="text-muted">Identifier akan menjadi URL (misal: <code>/halaman/visi-misi</code>). Gunakan tanda strip (-) untuk spasi.</small>
                </div>

                <div class="mb-4">
                    <label for="html_content" class="form-label fw-semibold">Konten Halaman</label>
                    <textarea id="editor" name="html_content"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success btn-submit">
                        Simpan Halaman Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Load CKEditor 5 dari CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script nonce="<?= generateCspNonce() ?>">
    ClassicEditor
        .create(document.querySelector('#editor'))
        .catch(error => {
            console.error(error);
        });
</script>