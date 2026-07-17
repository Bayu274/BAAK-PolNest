<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manajemen Konten Halaman</h2>
    <!-- Tombol tambah halaman bisa disiapkan untuk pengembangan ke depan -->
    <button class="btn btn-secondary" disabled>+ Tambah Halaman Baru</button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover table-bordered mb-0">
            <thead class="table-dark">
                <tr>
                    <th width="5%">No</th>
                    <th>Identifier Halaman</th>
                    <th width="20%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pages)): ?>
                    <?php $no = 1; foreach ($pages as $p): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <!-- Pastikan key 'page_identifier' sesuai dengan nama kolom di database-mu -->
                            <td class="fw-bold"><?php echo htmlspecialchars($p['page_identifier'] ?? $p['identifier'] ?? 'Unknown'); ?></td>
                            <td>
                                <a href="<?php echo rtrim(BASE_URL, '/'); ?>/admin/pages/edit/<?php echo htmlspecialchars($p['page_identifier'] ?? $p['identifier'] ?? ''); ?>" class="btn btn-sm btn-primary">
                                    Edit Konten
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">Belum ada halaman statis yang terdaftar di database.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>