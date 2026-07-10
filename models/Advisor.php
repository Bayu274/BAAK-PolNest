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
}
