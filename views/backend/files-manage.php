<!-- views/backend/files-manage.php -->
<div class="container-fluid mt-4">
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'success'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i> File berhasil diunggah!
            </div>
        <?php elseif ($_GET['status'] === 'deleted'): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-trash-fill"></i> File berhasil dihapus.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['import_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= e($_SESSION['import_error']); ?>
        </div>
        <?php unset($_SESSION['import_error']); ?>
    <?php endif; ?>
    <div class="row">
        <!-- Form Upload -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-cloud-arrow-up"></i> Unggah File Baru</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>admin/files/upload" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

                        <div class="mb-3">
                            <label for="file_category" class="form-label fw-bold">Kategori Dokumen</label>
                            <select name="file_category" id="file_category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>"><?php echo e(ucwords(str_replace('_', ' ', $cat))); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="document_file" class="form-label fw-bold">Pilih File (PDF / DOCX)</label>
                            <input type="file" name="document_file" id="document_file" class="form-control" accept=".pdf,.docx" required>
                            <div class="form-text text-muted">Maksimal ukuran 10 MB. Mengunggah file pada kategori yang sama akan menonaktifkan file lama secara otomatis.</div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-submit"><i class="bi bi-upload"></i> Simpan File</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel File Aktif -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-folder2-open"></i> Dokumen Aktif Saat Ini</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th>Nama File</th>
                                <th>Tgl Unggah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada dokumen yang diunggah.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo e(strtoupper(str_replace('_', ' ', $file['file_category']))); ?></span></td>
                                    <td class="fw-bold"><?php echo e($file['file_name']); ?></td>
                                    <td>
                                        <?php 
                                        $timestamp = strtotime($file['uploaded_at']);
                                        echo e($timestamp ? date('d M Y H:i', $timestamp) : $file['uploaded_at']);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>storage/uploads/<?php echo e($file['file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" download>
                                            <i class="bi bi-download"></i>
                                        </a>

                                        <form action="<?= BASE_URL ?>admin/files/delete" method="POST" class="d-inline" onsubmit="return confirm('Hapus file ini?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                                            <input type="hidden" name="file_id" value="<?php echo e($file['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
