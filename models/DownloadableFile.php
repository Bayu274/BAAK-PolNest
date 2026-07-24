<?php
/**
 * BAAK-PolNest - Downloadable File Model
 * Branch: feature/downloadable-files
 */

class DownloadableFile {
    private ?PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Mengambil daftar semua file yang sedang aktif
     */
    public function getActiveFiles(): array {
        $stmt = $this->db->query(
            "SELECT id, file_category, file_name, file_path, uploaded_at 
             FROM downloadable_files 
             WHERE is_active = 1 
             ORDER BY uploaded_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil semua file_path yang aktif (untuk orphan cleanup)
     */
    public function getActiveFileNames(): array {
        $stmt = $this->db->query(
            "SELECT file_path FROM downloadable_files WHERE is_active = 1"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengganti file lama dengan yang baru dalam satu Transaksi Atomik.
     * Menggunakan FOR UPDATE lock untuk mencegah race condition.
     */
    public function replaceByCategory(string $category, string $fileName, string $filePath, int $adminId): bool {
        $this->db->beginTransaction();

        try {
            // Lock existing active rows (block concurrent reads)
            $stmtLock = $this->db->prepare(
                "SELECT id FROM downloadable_files 
                 WHERE file_category = :cat AND is_active = 1 
                 FOR UPDATE"
            );
            $stmtLock->execute([':cat' => $category]);

            // Update (soft-deactivate) baris lama
            $stmtArchive = $this->db->prepare(
                "UPDATE downloadable_files SET is_active = 0 
                 WHERE file_category = :cat AND is_active = 1"
            );
            $stmtArchive->execute([':cat' => $category]);

            // Insert file baru
            $stmtInsert = $this->db->prepare(
                "INSERT INTO downloadable_files (file_category, file_name, file_path, uploaded_by, is_active, uploaded_at) 
                 VALUES (:cat, :name, :path, :admin_id, 1, NOW())"
            );
            $stmtInsert->execute([
                ':cat' => $category,
                ':name' => $fileName,
                ':path' => $filePath,
                ':admin_id' => $adminId,
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
