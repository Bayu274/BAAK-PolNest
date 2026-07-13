<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Konten Halaman</h2>
        <a href="/admin/dashboard" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
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
            <h5 class="mb-0">Form Edit Halaman: <span class="fw-light">SOP Pendaftaran Cuti</span></h5>
        </div>
        <div class="card-body">
            <form action="/admin/pages/update" method="POST">
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <input type="hidden" name="identifier" value="sop-cuti">

                <div class="mb-4">
                    <label for="html_content" class="form-label text-muted">Gunakan editor di bawah ini untuk mengubah isi konten, menebalkan teks, atau membuat daftar <em>(list)</em>. Perubahan akan langsung terlihat oleh mahasiswa di halaman publik.</label>
                    
                    <textarea class="form-control rich-text-editor" id="html_content" name="html_content" rows="15">
                        <h2>Prosedur Pengajuan Cuti Akademik</h2>
                        <p>Berikut adalah langkah-langkah yang harus dilakukan mahasiswa:</p>
                        <ol>
                            <li>Mengunduh form cuti akademik di halaman ini.</li>
                            <li>Mengisi data diri secara lengkap.</li>
                            <li>Meminta tanda tangan Dosen Wali.</li>
                            <li>Menyerahkan ke loket BAAK.</li>
                        </ol>
                    </textarea>
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

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '.rich-text-editor',
        menubar: false,
        plugins: 'lists link table',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link',
        height: 400
    });
</script>