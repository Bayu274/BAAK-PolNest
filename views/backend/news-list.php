<main class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>Daftar Berita</h3>
        <a href="/admin/news/create" class="btn btn-primary">+ Tambah Berita</a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Judul</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($newsList)): ?>
                    <?php foreach ($newsList as $news): ?>
                    <tr>
                        <td><?= htmlspecialchars($news['title']) ?></td>
                        <td><?= date('d-m-Y', strtotime($news['created_at'])) ?></td>
                        <td>
                          <a href="/admin/news/edit?id=<?= $news['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
    
                          <a href="/admin/news/delete?id=<?= $news['id'] ?>" 
                             class="btn btn-sm btn-danger" 
                             onclick="return confirm('Apakah Anda yakin ingin menghapus berita ini?')">Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">Belum ada berita yang dipublikasikan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>