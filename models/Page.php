<?php

class Page {
    private $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function getByIdentifier($identifier) {
        $stmt = $this->db->prepare("SELECT * FROM pages_content WHERE page_identifier = :identifier LIMIT 1");
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
}