<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Berita & Pengumuman</h2>
        <a href="/admin/news/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Berita Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="45%">Judul Berita</th>
                            <th width="15%">Tanggal Dibuat</th>
                            <th width="15%">Terakhir Edit</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td>
                                <strong>Sistem Informasi Pendaftaran Kegiatan Kampus (SIPKK) Siap Digunakan</strong><br>
                                <small class="text-muted">/berita/sistem-informasi-pendaftaran-kegiatan-kampus-sipkk-siap-digunakan</small>
                            </td>
                            <td>08 Jul 2026</td>
                            <td>08 Jul 2026</td>
                            <td class="text-center">
                                <a href="/admin/news/edit?id=1" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <form action="/admin/news/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus berita ini? Tindakan ini tidak dapat dibatalkan.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                    <input type="hidden" name="id" value="1">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</main>