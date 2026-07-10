<?php
class News {
    private $db;
    public function __construct() {
        $this->db = getDbConnection();
    }

    public function getAll($limit = null) {
        $query = "SELECT * FROM news ORDER BY created_at DESC";
        if ($limit) $query .= " LIMIT " . (int)$limit;
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO news (title, slug, content, thumbnail_image, created_by) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$data['title'], $data['slug'], $data['content'], $data['thumbnail'], $data['created_by']]);
    }
    // Tambahkan di models/News.php
    public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update($data) {
    if ($data['thumbnail'] !== null) {
        // Jika ada gambar baru yang diupload, update juga kolom thumbnail_image
        $stmt = $this->db->prepare("UPDATE news SET title = ?, slug = ?, content = ?, thumbnail_image = ? WHERE id = ?");
        return $stmt->execute([
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['thumbnail'], 
            $data['id']
        ]);
    } else {
        // Jika TIDAK ADA gambar baru, abaikan update pada thumbnail_image
        $stmt = $this->db->prepare("UPDATE news SET title = ?, slug = ?, content = ? WHERE id = ?");
        return $stmt->execute([
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['id']
        ]);
    }
}
    public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM news WHERE id = ?");
    return $stmt->execute([$id]);
}
}