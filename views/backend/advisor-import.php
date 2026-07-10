<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Impor Data Dosen Pembimbing</h5>
        </div>
        <div class="card-body">
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                <div class="alert alert-success">
                    Data pembimbing berhasil diimpor dan diperbarui secara keseluruhan!
                </div>
            <?php endif; ?>

            <div class="alert alert-warning border-warning">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Perhatian Penting!</strong><br>
                Mengunggah file CSV baru akan <b>MENGHAPUS SELURUH DATA LAMA</b> dan menggantinya dengan data dari file yang Anda unggah. Pastikan file Anda mencakup seluruh mahasiswa aktif.
            </div>

            <form action="/admin/import-csv" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin menimpa seluruh data database dengan file CSV ini?');">
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

                <div class="mb-4">
                    <label for="csv_file" class="form-label fw-bold">Pilih File (.csv)</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                    <div class="form-text">Maksimal ukuran file 5 MB.</div>
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
                </div>

                <button type="submit" class="btn btn-danger px-4">
                    <i class="bi bi-upload"></i> Proses Impor Data
                </button>
            </form>
        </div>
    </div>
</div>