<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold" style="color: #003366;">
            <i class="bi bi-newspaper me-2"></i>Manajemen Berita
        </h2>
        <a href="<?= BASE_URL ?>admin/news/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Berita
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" width="5%" class="text-center">No</th>
                            <th scope="col">Judul Berita</th>
                            <th scope="col" width="20%">Tanggal Publikasi</th>
                            <th scope="col" width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($news)): ?>
                            <?php $no = 1; foreach ($news as $item): ?>
                                <tr>
                                    <td class="text-center fw-bold text-secondary"><?= $no++ ?></td>
                                    <td>
                                        <span class="fw-medium"><?= htmlspecialchars($item['title']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d-m-Y', strtotime($item['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>admin/news/edit?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit Berita">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form action="<?= BASE_URL ?>admin/news/delete" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus berita ini? Tindakan ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Berita">
                                                <i class="bi bi-trash3"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Belum ada berita yang dipublikasikan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>