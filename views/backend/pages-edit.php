<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Konten Halaman: <span class="fw-light"><?= e($identifier) ?></span></h2>
        <!-- Memperbaiki URL kembali agar dinamis ke menu pages atau dashboard admin -->
        <a href="<?= BASE_URL ?>admin/pages" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Halaman
        </a>
    </div>

    <?php if (!empty($_SESSION['page_edit_flash'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> <?= e($_SESSION['page_edit_flash']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['page_edit_flash']); ?>
    <?php endif; ?>

    <?php if (!$page): ?>
        <div class="alert alert-warning" role="alert">
            Data halaman dengan identifier "<?= e($identifier) ?>" belum ada di database. Simpan untuk membuat konten baru (jika didukung), atau periksa kembali identifier-nya.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Form Edit Halaman</h5>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/pages/save/<?= e($identifier) ?>" method="POST">

                <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                <input type="hidden" name="identifier" value="<?= e($identifier) ?>">

                <div class="mb-4">
                    <label for="html_content" class="form-label text-muted">Gunakan editor di bawah ini untuk mengubah isi konten, menebalkan teks, atau membuat daftar <em>(list)</em>. Perubahan akan langsung terlihat oleh mahasiswa di halaman publik.</label>

                    <textarea class="form-control rich-text-editor" id="html_content" name="html_content" rows="15"><?= e($page['html_content'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success btn-lg px-4 btn-submit">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Implementasi CKEditor 5 Classic -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<style>
    /* Mengatur tinggi minimum area ketik agar seimbang dengan kebutuhan halaman panjang */
    .ck-editor__editable_inline {
        min-height: 400px;
    }
</style>
<script nonce="<?= generateCspNonce() ?>">
    ClassicEditor
        .create(document.querySelector('#html_content'), {
            toolbar: [ 'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'insertTable', 'blockQuote' ]
        })
        .catch(error => {
            console.error(error);
        });
</script>