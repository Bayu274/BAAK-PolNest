<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Impor Data Dosen Pembimbing</h5>
            <a href="<?= BASE_URL ?>admin/data-pembimbing" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Data Pembimbing
            </a>
        </div>
        <div class="card-body">
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                <div class="alert alert-success">
                    Data pembimbing berhasil diimpor dan diperbarui secara keseluruhan!
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['import_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= e($_SESSION['import_error']); ?>
                </div>
                <?php unset($_SESSION['import_error']); ?>
            <?php endif; ?>

            <div class="alert alert-warning border-warning">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Perhatian Penting!</strong><br>
                Mengunggah file baru (CSV / Excel) akan <b>MENGHAPUS SELURUH DATA LAMA</b> dan menggantinya dengan data dari file yang Anda unggah. Pastikan file Anda mencakup seluruh mahasiswa aktif.
            </div>

            <form action="<?= BASE_URL ?>admin/import-csv" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin menimpa seluruh data database dengan file ini?');">
                
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <div class="mb-4">
                    <label for="csv_file" class="form-label fw-bold">Pilih File (.csv atau .xlsx)</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.xlsx" required>
                    <div class="form-text">Maksimal ukuran file 5 MB.</div>
                    <a href="<?= BASE_URL ?>admin/import-csv/template" class="btn btn-outline-secondary btn-sm mt-2">
                        <i class="bi bi-download"></i> Unduh Template CSV
                    </a>
                    <a href="<?= BASE_URL ?>admin/import-csv/template-xlsx" class="btn btn-outline-success btn-sm mt-2">
                        <i class="bi bi-file-earmark-excel"></i> Unduh Template Excel (.xlsx)
                    </a>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Format Kolom yang Diwajibkan:</label>
                    <table class="table table-bordered table-sm w-auto">
                        <thead class="table-light">
                            <tr>
                                <th>nim</th>
                                <th>student_name</th>
                                <th>advisor_name</th>
                                <th>advisor_type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>21010001</td>
                                <td>Andi Wijaya</td>
                                <td>Bpk. Budi S.Kom</td>
                                <td>Wali <i>(atau Magang / TA)</i></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="form-text">
                        <i class="bi bi-file-earmark-excel text-success"></i> <strong>Untuk file Excel:</strong> satu sheet boleh berisi
                        <strong>beberapa tabel</strong> — setiap tabel diawali baris header
                        <code>nim, student_name, advisor_name, advisor_type</code> dan dapat diberi
                        baris judul di atasnya (mis. "TABEL 1 — DOSEN WALI"). Baris judul &amp; baris
                        kosong dilewati otomatis.
                    </div>
                </div>

                <button type="submit" class="btn btn-danger px-4 btn-submit">
                    <i class="bi bi-upload"></i> Proses Impor Data
                </button>
            </form>
        </div>
    </div>
</div>