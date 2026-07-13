<?php

class News {
    private $db;

    public function __construct() {
        // Fungsi getDbConnection() ini adalah bagian dari /config/database.php yang dikerjakan Dev 1
        $this->db = getDbConnection(); 
    }

    public function getAll($limit = null) {
        $query = "SELECT * FROM news ORDER BY created_at DESC";
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($query);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM news WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        // Parameter admin_id (created_by) juga disiapkan untuk audit trail
        $stmt = $this->db->prepare("
            INSERT INTO news (title, slug, content, thumbnail_image, created_by) 
            VALUES (:title, :slug, :content, :thumbnail_image, :created_by)
        ");
        
        $stmt->execute([
            'title'           => $data['title'],
            'slug'            => $data['slug'],
            'content'         => $data['content'],
            'thumbnail_image' => $data['thumbnail_image'] ?? null,
            'created_by'      => $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE news 
            SET title = :title, 
                slug = :slug, 
                content = :content, 
                thumbnail_image = COALESCE(:thumbnail_image, thumbnail_image) 
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'title'           => $data['title'],
            'slug'            => $data['slug'],
            'content'         => $data['content'],
            'thumbnail_image' => $data['thumbnail_image'] ?? null,
            'id'              => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM news WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}