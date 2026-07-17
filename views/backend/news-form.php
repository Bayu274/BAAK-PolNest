<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Judul dinamis tergantung sedang tambah atau edit berita -->
        <h2><?php echo isset($news) ? 'Edit Berita' : 'Tambah Berita'; ?></h2>
        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/admin/news" class="btn btn-secondary">
    Kembali ke Manajemen Berita
</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <!-- Form Action disesuaikan menggunakan BASE_URL -->
            <form action="<?php echo rtrim(BASE_URL, '/'); ?>/admin/news/<?php echo isset($news) ? 'update/' . $news['id'] : 'store'; ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label">Judul Berita</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($news['title'] ?? ''); ?>" required>
                </div>

                <div class="mb-4">
                    <label for="content" class="form-label">Isi Berita</label>
                    <!-- Menggunakan id="content" untuk berita -->
                    <textarea class="form-control" id="content" name="content" rows="15"><?php echo htmlspecialchars($news['content'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-save"></i> <?php echo isset($news) ? 'Simpan Perubahan' : 'Terbitkan Berita'; ?>
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
    // Selector menargetkan '#content' sesuai dengan form berita
    const textareaNews = document.querySelector('#content'); 
    
    if (textareaNews) {
        ClassicEditor
            .create(textareaNews)
            .catch(error => {
                console.error('Terjadi kesalahan saat memuat CKEditor:', error);
            });
    }
</script>