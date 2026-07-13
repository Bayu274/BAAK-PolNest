<?php
/**
 * BAAK-PolNest - Downloadable File Model
 * Branch: feature/downloadable-files
 */

class DownloadableFile {
    private ?PDO $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    /**
     * Mengambil daftar semua file yang sedang aktif
     */
    public function getActiveFiles(): array {
        $stmt = $this->db->query("SELECT * FROM downloadable_files WHERE is_active = 1 ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengganti file lama dengan yang baru dalam satu Transaksi Atomik.
     * Mengamankan data dari kegagalan query di tengah jalan.
     */
    public function replaceByCategory(string $category, string $fileName, string $filePath, int $adminId): bool {
        try {
            $this->db->beginTransaction();

            // 1. Arsipkan file lama di kategori yang sama (Soft Delete)
            $stmtArchive = $this->db->prepare("UPDATE downloadable_files SET is_active = 0 WHERE file_category = :cat AND is_active = 1");
            $stmtArchive->execute([':cat' => $category]);

            // 2. Masukkan record file baru
            $stmtInsert = $this->db->prepare("
                INSERT INTO downloadable_files (file_category, file_name, file_path, uploaded_by, is_active, created_at) 
                VALUES (:cat, :name, :path, :admin_id, 1, NOW())
            ");
            $stmtInsert->execute([
                ':cat' => $category,
                ':name' => $fileName,
                ':path' => $filePath,
                ':admin_id' => $adminId
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("Gagal Replace File: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Menonaktifkan file (Soft Delete)
     */
    public function deactivate(int $id): bool {
        $stmt = $this->db->prepare("UPDATE downloadable_files SET is_active = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}