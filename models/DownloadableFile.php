<?php
/**
 * BAAK-PolNest - Downloadable File Model
 * Branch: feature/downloadable-files
 */

class DownloadableFile {
    private ?PDO $db;
    private ?string $lastError = null;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Pesan error terakhir dari operasi database (untuk diagnostik)
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    /**
     * Mengambil daftar semua file yang sedang aktif
     */
    public function getActiveFiles(): array {
        try {
            $stmt = $this->db->query(
                "SELECT id, file_category, file_name, title, file_path, uploaded_at 
                 FROM downloadable_files 
                 WHERE is_active = 1 
                 ORDER BY uploaded_at DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::getActiveFiles -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil semua file_path yang aktif (untuk orphan cleanup)
     */
    public function getActiveFileNames(): array {
        try {
            $stmt = $this->db->query(
                "SELECT file_path FROM downloadable_files WHERE is_active = 1"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::getActiveFileNames -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil file aktif berdasarkan ID (untuk endpoint unduhan publik).
     */
    public function getById(int $id, bool $activeOnly = true): ?array {
        try {
            $sql = "SELECT id, file_category, file_name, title, file_path, uploaded_by, uploaded_at
                    FROM downloadable_files WHERE id = :id";
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row === false ? null : $row;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::getById -> " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil daftar kategori yang pernah dipakai (untuk datalist pada
     * form upload — admin bebas memakai kategori yang sudah ada atau
     * mengetik kategori baru).
     */
    public function getDistinctCategories(): array {
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT file_category FROM downloadable_files ORDER BY file_category ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::getDistinctCategories -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Menyimpan file baru sebagai AKTIF tanpa menonaktifkan file lain di
     * kategori yang sama. Dengan begitu admin boleh menyimpan banyak dokumen
     * (PDF/DOCX) dalam satu kategori — minimal 5 file atau lebih.
     *
     * $title: judul tampilan publik (opsional). Bila null/kosong, tampilan
     * memakai nama file ($fileName).
     */
    public function addFile(string $category, string $fileName, string $filePath, int $adminId, ?string $title = null): bool {
        try {
            $title = ($title === null) ? null : trim($title);
            $title = ($title === '') ? null : mb_substr($title, 0, 255);

            $stmt = $this->db->prepare(
                "INSERT INTO downloadable_files (file_category, file_name, title, file_path, uploaded_by, is_active, uploaded_at)
                 VALUES (:cat, :name, :title, :path, :admin_id, 1, NOW())"
            );
            return $stmt->execute([
                ':cat'      => $category,
                ':name'     => $fileName,
                ':title'    => $title,
                ':path'     => $filePath,
                ':admin_id' => $adminId,
            ]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::addFile -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menghapus baris secara permanen (hard delete). File fisik di-handle
     * oleh controller (unlink).
     */
    public function deleteById(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM downloadable_files WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Database Error di DownloadableFile::deleteById -> " . $e->getMessage());
            return false;
        }
    }
}
