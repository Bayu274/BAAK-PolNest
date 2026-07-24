<?php
/**
 * BAAK-PolNest - Advisor Model
 * Branch: feature/advisor-search
 */

class Advisor {
    private ?PDO $db;

    public function __construct() {
    $this->db = getDbConnection();
    }

    /**
     * Menormalkan input string: hapus spasi berlebih, ubah ke lowercase unicode aman
     */
    private function normalize(string $input): string {
        $cleanSpace = preg_replace('/\s+/u', ' ', trim($input));
        return mb_strtolower($cleanSpace, 'UTF-8');
    }

    /**
     * Mencari pembimbing berdasarkan NIM dan Nama Mahasiswa (Strict Exact Match)
     * @return array List data pembimbing jika ditemukan, array kosong jika gagal
     */
    public function findByNimAndName(string $nim, string $studentName): array {
        if (empty($nim) || empty($studentName)) {
            return [];
        }

        // Jalankan normalisasi string di sisi model
        $normalizedNim = $this->normalize($nim);
        $normalizedName = $this->normalize($studentName);

        // SQL murni tanpa LIKE, tanpa string concatenation. Aman dari SQL Injection.
        // Diurutkan berdasarkan tipe agar seragam (Wali -> Magang -> TA)
        $sql = "SELECT advisor_name, advisor_type 
                FROM student_advisors 
                WHERE nim = :nim AND student_name = :student_name
                ORDER BY FIELD(advisor_type, 'Wali', 'Magang', 'TA') ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nim' => $normalizedNim,
                ':student_name' => $normalizedName
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error internal tanpa mengekspos detail SQL ke end-user
            error_log("Database Error di Advisor::findByNimAndName -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Backup current data to CSV before atomic swap
     */
    private function backupCurrentData(): void {
        $backupDir = __DIR__ . '/../storage/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $stmt = $this->db->query("SELECT nim, student_name, advisor_name, advisor_type FROM student_advisors");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) return;

        $filename = $backupDir . 'advisor_backup_' . date('Y-m-d_His') . '.csv';
        $handle = fopen($filename, 'w');
        fputcsv($handle, ['nim', 'student_name', 'advisor_name', 'advisor_type']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        // Keep only last 5 backups
        $backups = glob($backupDir . 'advisor_backup_*.csv');
        usort($backups, function($a, $b) { return filemtime($b) - filemtime($a); });
        foreach (array_slice($backups, 5) as $old) {
            unlink($old);
        }
    }

    /**
     * Mengganti seluruh data dosen pembimbing menggunakan staging table (atomic swap).
     * Search tetap bisa dilakukan selama proses import berlangsung.
     * @param array $rows Data array multi-dimensi hasil parsing CSV
     */
    public function truncateAndReload(array $rows): void {
        if (empty($rows)) {
            throw new Exception("Data CSV kosong, proses impor dibatalkan.");
        }

        // Backup sebelum swap
        $this->backupCurrentData();

        $this->db->beginTransaction();

        try {
            // 1. Buat temporary table (copy struktur dari student_advisors)
            $this->db->exec("DROP TEMPORARY TABLE IF EXISTS tmp_student_advisors");
            $this->db->exec("CREATE TEMPORARY TABLE tmp_student_advisors LIKE student_advisors");

            // 2. Insert data ke staging dengan normalisasi lowercase
            $sqlInsert = "INSERT INTO tmp_student_advisors (nim, student_name, advisor_name, advisor_type) 
                          VALUES (:nim, :student_name, :advisor_name, :advisor_type)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            foreach ($rows as $row) {
                $stmtInsert->execute([
                    ':nim'          => $this->normalize($row['nim']),
                    ':student_name' => $this->normalize($row['student_name']),
                    ':advisor_name' => trim($row['advisor_name']),
                    ':advisor_type' => trim($row['advisor_type']),
                ]);
            }

            // 3. Atomic swap — DROP lama, RENAME staging jadi yang baru
            $this->db->exec("DROP TABLE student_advisors");
            $this->db->exec("RENAME TABLE tmp_student_advisors TO student_advisors");

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("Gagal Impor CSV: " . $e->getMessage());
            throw new Exception("Terjadi kesalahan sistem saat menyimpan data. Seluruh perubahan telah dibatalkan.");
        }
    }
}