<?php
/**
 * BAAK-PolNest - Advisor Model
 * Branch: feature/advisor-search
 */

class Advisor {
    private ?PDO $db;

    public function __construct() {
        // Mengambil koneksi dari berkas database.php yang diinstansiasi oleh Dev 1
        global $db; 
        $this->db = $db;
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
                WHERE LOWER(nim) = :nim AND LOWER(student_name) = :student_name
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
     * Menghapus data lama dan memasukkan data baru dalam satu transaksi yang aman.
     * Menggunakan DELETE (bukan TRUNCATE) agar mendukung Rollback InnoDB.
     * * @param array $rows Data array multi-dimensi hasil parsing CSV
     * @return bool True jika berhasil, melempar Exception jika gagal
     */
    public function truncateAndReload(array $rows): bool {
        if (empty($rows)) {
            throw new Exception("Data CSV kosong, proses impor dibatalkan.");
        }

        try {
            // 1. Mulai Transaksi
            $this->db->beginTransaction();

            // 2. Hapus seluruh data lama
            $stmtDelete = $this->db->prepare("DELETE FROM student_advisors");
            $stmtDelete->execute();

            // 3. Siapkan kueri Insert (Prepared Statement)
            $sqlInsert = "INSERT INTO student_advisors (nim, student_name, advisor_name, advisor_type) 
                          VALUES (:nim, :student_name, :advisor_name, :advisor_type)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            // 4. Lakukan Bulk Insert (Looping)
            foreach ($rows as $row) {
                $stmtInsert->execute([
                    ':nim'          => $row['nim'],
                    ':student_name' => $row['student_name'],
                    ':advisor_name' => $row['advisor_name'],
                    ':advisor_type' => $row['advisor_type']
                ]);
            }

            // 5. Jika semua eksekusi di atas mulus, simpan permanen
            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            // Jika ada 1 saja query yang gagal, batalkan SEMUA perubahan!
            $this->db->rollBack();
            error_log("Gagal Impor CSV: " . $e->getMessage());
            throw new Exception("Terjadi kesalahan sistem saat menyimpan data ke database. Seluruh perubahan telah dibatalkan.");
        }
    }
}