<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Konten Halaman</h2>
        <!-- Menggunakan BASE_URL sesuai aturan Dev 1 -->
        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/admin/pages" class="btn btn-secondary">
        Kembali ke Manajemen Halaman
        </a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Konten halaman berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <!-- Judul dibuat dinamis mengikuti data dari controller -->
            <h5 class="mb-0">Form Edit Halaman: <span class="fw-light"><?php echo htmlspecialchars($page['title'] ?? 'SOP'); ?></span></h5>
        </div>
        <div class="card-body">
            <!-- Action diubah ke rute yang benar (/admin/pages/save/{identifier}) sesuai perbaikan Dev 1 -->
            <!-- Hapus garis miring sebelum admin jika BASE_URL sudah diperbaiki, dan gunakan alternatif pemanggilan array jika key-nya berbeda -->
                 <form action="<?php echo rtrim(BASE_URL, '/'); ?>/admin/pages/save/<?php echo htmlspecialchars($_GET['id'] ?? $page['identifier'] ?? 'sop-cuti'); ?>" method="POST">
    
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
                <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($_GET['id'] ?? $page['identifier'] ?? 'sop-cuti'); ?>">

                <div class="mb-4">
                    <label for="html_content" class="form-label text-muted">Gunakan editor di bawah ini untuk mengubah isi konten, menebalkan teks, atau membuat daftar <em>(list)</em>. Perubahan akan langsung terlihat oleh mahasiswa di halaman publik.</label>
                    
                    <!-- Textarea memuat data dari database ($page['html_content']), bukan teks statis -->
                    <textarea class="form-control rich-text-editor" id="html_content" name="html_content" rows="15"><?php echo htmlspecialchars($page['html_content'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success btn-lg px-4">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
    /* Mengatur tinggi minimal area pengetikan teks CKEditor */
    .ck-editor__editable_inline {
        min-height: 300px;
    }
</style>

<!-- Load library CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<script>
    // Selector sudah diperbaiki menargetkan '#html_content'
    const textareaElement = document.querySelector('#html_content'); 
    
    if (textareaElement) {
        ClassicEditor
            .create(textareaElement)
            .catch(error => {
                console.error('Terjadi kesalahan saat memuat CKEditor:', error);
            });
    }
</script>