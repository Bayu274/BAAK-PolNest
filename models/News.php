<?php
class News {
    private $db;
    public function __construct() {
        $this->db = getDbConnection();
    }

    public function getAll($limit = null) {
        $sql = "SELECT * FROM news ORDER BY created_at DESC";
        if ($limit) {
            // Pastikan limit adalah angka untuk keamanan
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
    // Pastikan 'thumbnail_image' tertulis di sini!
    $sql = "INSERT INTO news (title, slug, content, thumbnail_image, created_by, created_at) 
            VALUES (:title, :slug, :content, :thumbnail_image, :created_by, NOW())";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute($data);
}
    // Tambahkan di models/News.php
    public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update($data) {
    if ($data['thumbnail_image'] !== null) {
        // Jika ada gambar baru yang diupload, update juga kolom thumbnail_image
        $stmt = $this->db->prepare("UPDATE news SET title = ?, slug = ?, content = ?, thumbnail_image = ? WHERE id = ?");
        return $stmt->execute([
            $data['title'], 
            $data['slug'], 
            $data['content'], 
            $data['thumbnail_image'], // <--- Ini sudah diperbaiki
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
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM news WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}