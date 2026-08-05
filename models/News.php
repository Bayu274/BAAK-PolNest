<?php
class News {
    private $db;
    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Ambil daftar berita. $activeOnly=true hanya mengembalikan berita yang
     * dipublikasikan (is_active = 1) — dipakai untuk halaman publik.
     * Backward-compatible: getAll() / getAll(6) / getAll(null, $keyword)
     * tetap berfungsi seperti sebelumnya (admin melihat semua termasuk draft).
     */
    public function getAll($limit = null, $keyword = null, $activeOnly = false) {
        $sql = "SELECT id, title, slug, content, thumbnail_image, created_by, created_at, is_active FROM news";
        $params = [];
        $conditions = [];
        if (!empty($keyword)) {
            $conditions[] = "(title LIKE ? OR content LIKE ?)";
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }
        if ($activeOnly) {
            $conditions[] = "is_active = 1";
        }
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
    // Pastikan 'thumbnail_image' tertulis di sini!
    $sql = "INSERT INTO news (title, slug, content, thumbnail_image, created_by, is_active, created_at) 
            VALUES (:title, :slug, :content, :thumbnail_image, :created_by, :is_active, NOW())";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute($data);
}
    // Tambahkan di models/News.php
    public function getById($id) {
    $stmt = $this->db->prepare("SELECT id, title, slug, content, thumbnail_image, created_by, created_at, is_active FROM news WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update($data) {
    if ($data['thumbnail_image'] !== null) {
        // Jika ada gambar baru yang diupload, update juga kolom thumbnail_image
        $stmt = $this->db->prepare("UPDATE news SET title = ?, slug = ?, content = ?, is_active = ?, thumbnail_image = ? WHERE id = ?");
        return $stmt->execute([
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['is_active'], 
            $data['thumbnail_image'], // <--- Ini sudah diperbaiki
            $data['id']
        ]);
    } else {
        // Jika TIDAK ADA gambar baru, abaikan update pada thumbnail_image
        $stmt = $this->db->prepare("UPDATE news SET title = ?, slug = ?, content = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['is_active'], 
            $data['id']
        ]);
    }
}
    public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM news WHERE id = ?");
    return $stmt->execute([$id]);
}
    /**
     * Ambil berita per slug. $activeOnly=true (halaman publik) hanya
     * mengembalikan berita yang dipublikasikan. Default false dipakai
     * generateSlug() agar slug unik terhadap SEMUA berita (termasuk draft).
     */
    public function getBySlug($slug, $activeOnly = false) {
        $sql = "SELECT id, title, slug, content, thumbnail_image, created_by, created_at, is_active FROM news WHERE slug = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
