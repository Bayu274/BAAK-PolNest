<?php

class Page {
    private $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    // METHOD BARU: Untuk mengambil semua daftar halaman
    public function getAll() {
        $stmt = $this->db->query("SELECT id, page_identifier, title, html_content, updated_by, last_updated FROM pages_content ORDER BY page_identifier ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdentifier($identifier) {
        $stmt = $this->db->prepare("SELECT id, page_identifier, title, html_content, updated_by, last_updated FROM pages_content WHERE page_identifier = :identifier LIMIT 1");
        $stmt->execute(['identifier' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateContent($identifier, $htmlContent, $adminId) {
        // Kolom updated_by digunakan untuk melacak admin mana yang terakhir kali mengedit halaman
        $stmt = $this->db->prepare("
            UPDATE pages_content 
            SET html_content = :html_content, 
                updated_by = :updated_by 
            WHERE page_identifier = :identifier
        ");
        
        return $stmt->execute([
            'html_content' => $htmlContent,
            'updated_by'   => $adminId,
            'identifier'   => $identifier
        ]);
    }

    // METHOD BARU: Untuk menambahkan halaman baru (Create)
    public function create($identifier, $title, $htmlContent, $adminId) {
        $stmt = $this->db->prepare("
            INSERT INTO pages_content (page_identifier, title, html_content, updated_by) 
            VALUES (:identifier, :title, :html_content, :updated_by)
        ");
        
        return $stmt->execute([
            'identifier'   => $identifier,
            'title'        => $title,
            'html_content' => $htmlContent,
            'updated_by'   => $adminId
        ]);
    }

    // METHOD BARU: Untuk menghapus halaman (Delete)
    public function delete($identifier) {
        $stmt = $this->db->prepare("DELETE FROM pages_content WHERE page_identifier = :identifier");
        return $stmt->execute(['identifier' => $identifier]);
    }
}