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
     * Statistik ringkas data pembimbing (total baris, mahasiswa unik,
     * dosen unik, dan waktu import terakhir). Dipakai untuk halaman review admin.
     *
     * @return array{total:int, students:int, advisors:int, last_import:?string}
     */
    public function getStats(): array {
        $stats = ['total' => 0, 'students' => 0, 'advisors' => 0, 'last_import' => null];
        try {
            $stats['total']       = (int) $this->db->query("SELECT COUNT(*) FROM student_advisors")->fetchColumn();
            $stats['students']    = (int) $this->db->query("SELECT COUNT(DISTINCT nim) FROM student_advisors")->fetchColumn();
            $stats['advisors']    = (int) $this->db->query("SELECT COUNT(DISTINCT advisor_name) FROM student_advisors")->fetchColumn();
            $last                 = $this->db->query("SELECT MAX(imported_at) FROM student_advisors")->fetchColumn();
            $stats['last_import'] = $last ?: null;
        } catch (PDOException $e) {
            error_log("Database Error di Advisor::getStats -> " . $e->getMessage());
        }
        return $stats;
    }

    /**
     * Menghitung jumlah baris yang cocok dengan filter (keyword + tipe).
     * Sama persis dengan getRecords() agar nomor halaman konsisten.
     *
     * @param array $filters ['keyword' => string|null, 'advisor_type' => string|null]
     */
    public function countRecords(array $filters = []): int {
        [$whereSql, $params] = $this->buildFilterQuery($filters);
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM student_advisors {$whereSql}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database Error di Advisor::countRecords -> " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Menampilkan daftar data pembimbing langsung dari database (real-time)
     * dengan filter opsional dan pagination. Ordering stabil: nim lalu tipe lalu id.
     *
     * @param array $filters ['keyword' => string|null, 'advisor_type' => string|null]
     * @return array List data, array kosong jika query gagal
     */
    public function getRecords(array $filters = [], int $limit = 30, int $offset = 0): array {
        [$whereSql, $params] = $this->buildFilterQuery($filters);
        $sql = "SELECT id, nim, student_name, advisor_name, advisor_type, imported_at
                FROM student_advisors
                {$whereSql}";

        $sql .= " ORDER BY nim ASC, advisor_type ASC, id ASC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', max(1, (int) $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, (int) $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error di Advisor::getRecords -> " . $e->getMessage());
            return [];
        }
    }

    /**
     * Helper query filter bersama supaya getRecords() dan countRecords()
     * selalu sepakat pada kondisi yang sama (keyword + tipe pembimbing).
     *
     * @param array $filters ['keyword' => string|null, 'advisor_type' => string|null]
     * @return array [string $conditionSql, array $params]
     */
    private function buildFilterQuery(array $filters = []): array {
        $conditions = [];
        $params     = [];

        $keyword = isset($filters['keyword']) ? trim((string) $filters['keyword']) : '';
        if ($keyword !== '') {
            $conditions[] = "(LOWER(nim) LIKE :kw OR LOWER(student_name) LIKE :kw OR LOWER(advisor_name) LIKE :kw)";
            $params[':kw'] = '%' . $this->normalize($keyword) . '%';
        }

        $type = isset($filters['advisor_type']) ? trim((string) $filters['advisor_type']) : '';
        if (in_array($type, ['Wali', 'Magang', 'TA'], true)) {
            $conditions[]   = "advisor_type = :adv_type";
            $params[':adv_type'] = $type;
        }

        $whereSql = "WHERE 1 = 1";
        if ($conditions !== []) {
            $whereSql .= " AND " . implode(" AND ", $conditions);
        }

        return [$whereSql, $params];
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
     *
     * CATATAN: staging table sengaja berupa tabel REGULER (bukan TEMPORARY) karena
     * MySQL/MariaDB tidak mengizinkan RENAME tabel TEMPORARY menjadi tabel permanen.
     * Swap dilakukan atomik via satu perintah RENAME TABLE. Jika gagal, tabel lama
     * dikembalikan (recovery) sehingga sistem tidak pernah kehilangan data.
     *
     * @param array $rows Data array multi-dimensi hasil parsing CSV
     */
    public function truncateAndReload(array $rows): void {
        if (empty($rows)) {
            throw new Exception("Data CSV kosong, proses impor dibatalkan.");
        }

        // Backup sebelum swap
        $this->backupCurrentData();

        try {
            // 1. Buat staging table (copy struktur dari student_advisors)
            $this->db->exec("DROP TABLE IF EXISTS tmp_student_advisors");
            $this->db->exec("CREATE TABLE tmp_student_advisors LIKE student_advisors");

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

            // 3. Atomic swap — ganti nama kedua tabel dalam satu perintah (all-or-nothing)
            $this->db->exec("RENAME TABLE student_advisors TO student_advisors_old, tmp_student_advisors TO student_advisors");

            // 4. Hapus tabel lama
            $this->db->exec("DROP TABLE IF EXISTS student_advisors_old");

        } catch (Throwable $e) {
            // Recovery: pastikan tabel utama selalu ada dan jangan tinggalkan sisa staging
            try {
                $this->db->exec("DROP TABLE IF EXISTS tmp_student_advisors");
                $hasMain = $this->db->query("SHOW TABLES LIKE 'student_advisors'")->fetchColumn();
                $hasOld = $this->db->query("SHOW TABLES LIKE 'student_advisors_old'")->fetchColumn();
                if (!$hasMain && $hasOld) {
                    $this->db->exec("RENAME TABLE student_advisors_old TO student_advisors");
                }
                $this->db->exec("DROP TABLE IF EXISTS student_advisors_old");
            } catch (Throwable $ignored) {
                // Recovery terakhir gagal — biarkan error asli yang dilaporkan
            }
            error_log("Gagal Impor CSV: " . $e->getMessage());
            throw new Exception("Terjadi kesalahan sistem saat menyimpan data. Seluruh perubahan telah dibatalkan.");
        }
    }
}