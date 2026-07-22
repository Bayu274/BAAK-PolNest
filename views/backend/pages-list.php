<main class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold" style="color: #003366;">
            <i class="bi bi-file-earmark-text me-2"></i>Manajemen Halaman
        </h2>
        <!-- Tombol Tambah Halaman -->
        <a href="<?= BASE_URL ?>admin/pages/create" class="btn btn-primary shadow-sm">
            + Tambah Halaman Baru
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="ps-4">No</th>
                            <th scope="col">Identifier (URL)</th>
                            <th scope="col">Judul Halaman</th>
                            <th scope="col">Terakhir Diperbarui</th>
                            <th scope="col" class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pages)): ?>
                            <?php $no = 1; foreach ($pages as $p): ?>
                                <tr>
                                    <td class="ps-4 align-middle"><?= $no++ ?></td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($p['page_identifier']) ?></span>
                                    </td>
                                    <td class="align-middle fw-semibold">
                                        <?= htmlspecialchars($p['title'] ?? $p['page_identifier']) ?>
                                    </td>
                                    <td class="align-middle text-muted">
                                        <?= isset($p['updated_at']) ? date('d M Y H:i', strtotime($p['updated_at'])) : '-' ?>
                                    </td>
                                    <td class="align-middle text-center pe-4">
                                        <!-- Tombol Edit -->
                                        <a href="<?= BASE_URL ?>admin/pages/edit/<?= htmlspecialchars($p['page_identifier']) ?>" class="btn btn-sm btn-primary">
                                            Edit
                                        </a>
                                        
                                        <!-- Form & Tombol Hapus -->
                                        <form action="<?= BASE_URL ?>admin/pages/delete" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus halaman ini?');">
                                            <!-- Token dari listAdmin() yang baru -->
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <input type="hidden" name="identifier" value="<?= htmlspecialchars($p['page_identifier']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Belum ada data halaman yang tersimpan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>