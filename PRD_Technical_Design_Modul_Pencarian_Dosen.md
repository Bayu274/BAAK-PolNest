# Modul Pencarian Dosen — PRD & Technical Design

**Proyek:** Portal Informasi BAAK — Politeknik Nest
**Modul:** Pencarian Dosen Pembimbing (Wali/PI/TA)
**Referensi:** PRD.md v4.0, Bagian 5

---

## Halaman 1: Product Requirement Document (PRD)

### User Persona
Mahasiswa Politeknik Nest yang membutuhkan informasi mengenai Dosen Wali, Pembimbing Magang (PI), atau Dosen Pembimbing TA secara mandiri. Mereka membutuhkan akses cepat tanpa harus datang langsung ke kantor fisik BAAK.

### User Story
Sebagai seorang mahasiswa, saya ingin mencari dosen pembimbing saya dengan memasukkan NIM dan Nama Lengkap sekaligus, agar privasi data saya tetap aman dan tidak mudah diintip atau disalahgunakan oleh orang lain.

### User Journey
1. Membuka halaman Modul Pencarian Dosen pada portal.
2. Memasukkan NIM dan Nama Lengkap pada kolom yang disediakan.
3. Menekan tombol cari atau memicu pencarian otomatis.
4. Menunggu sistem mencocokkan data secara instan tanpa memuat ulang halaman (without reload).
5. Melihat informasi nama dosen pembimbing beserta jenisnya (Wali/Magang/TA) langsung di layar.

### Acceptance Criteria
1. Dapat melakukan pencarian instan menggunakan AJAX/Fetch API.
2. Hasil pencarian hanya muncul jika NIM dan Nama Lengkap cocok bersamaan (Exact Match).
3. Menolak pencarian parsial (tidak boleh menggunakan fungsi `LIKE`).
4. Menampilkan pesan error yang jelas dan ramah jika data tidak ditemukan.
5. Dilindungi lapis pertahanan Rate Limiting (pembatasan jumlah pencarian per menit per IP).

---

## Halaman 2: Technical Design

### Architecture
- PHP Native + MySQL (arsitektur prosedural/modular terpisah tanpa framework).
- Komunikasi data dari frontend ke backend menggunakan AJAX / Fetch API.

### API
- `POST /controllers/search_advisor.php`
  - Payload: `{ "nim": "string", "student_name": "string" }`
  - Response: JSON object berisi data dosen atau pesan error.

### Business Logic
1. Mengamankan endpoint dengan memeriksa batasan Rate Limiting per IP.
2. Menangkap input `nim` dan `student_name` dari frontend.
3. Melakukan normalisasi input (`trim`, lowercase, kompresi spasi ganda).
4. Mengeksekusi query database dengan pencarian ketat menggunakan parameter `AND`.
5. Mengembalikan data hasil pencarian dalam format JSON ke frontend.

### Permission
- Public Access: Modul ini dapat diakses oleh publik (mahasiswa & dosen) tanpa perlu melakukan login terlebih dahulu.

### Database
- `student_advisors` table: `id`, `nim`, `student_name`, `advisor_name`, `advisor_type`.
- Optimasi: Menerapkan Composite Index pada kolom `(nim, student_name)` untuk mempercepat query pencarian.

### Validation
1. Mandatory Fields: Memastikan input `nim` dan `student_name` tidak kosong sebelum query dijalankan.
2. Exact Match: Menggunakan operator `=` (sama dengan), bukan `LIKE`.
3. SQL Injection Prevention: Wajib menggunakan Prepared Statements (PDO/MySQLi).

### Testing
1. Pencarian Sukses: Memastikan hasil keluar jika NIM & Nama Lengkap cocok 100%.
2. Pencarian Parsial Ditolak: Memastikan data tersembunyi jika user hanya mengisi NIM saja atau nama saja.
3. SQL Injection Blocked: Memastikan sistem aman saat input disisipkan karakter aneh (`' OR 1=1 --`).
4. Rate Limit Triggered: Memastikan sistem memblokir request jika user melakukan pencarian terlalu cepat secara berulang-ulang.

---

## Catatan Terbuka (Belum Diputuskan)

> Bagian ini sengaja dipisah dari isi utama di atas — poin-poin di bawah **belum** merupakan keputusan final tim/klien, hanya dicatat supaya tidak hilang sebelum dibahas di evaluasi Jumat.

1. **Status Rate Limiting** — di PRD.md Bagian 5 & 11, rate limiting masih berstatus "usul tim, belum dikonfirmasi klien". Dokumen ini menuliskannya seolah sudah pasti dikerjakan (Business Logic #1, Testing #4). Perlu ditegaskan statusnya sebelum dikerjakan atau dilampirkan sebagai dokumentasi resmi.
2. **Mekanisme penyimpanan counter rate limit** — PHP native tanpa framework tidak punya middleware bawaan. Perlu diputuskan: tabel MySQL (IP + timestamp + counter) atau mekanisme lain. Session tidak reliable untuk ini.
3. **Response saat mahasiswa punya >1 jenis pembimbing** — satu NIM+Nama bisa cocok di lebih dari satu baris (Wali, PI, TA sekaligus). API saat ini didesain sebagai "JSON object" tunggal; perlu diputuskan apakah response berupa array semua match, atau user memilih jenis pembimbing dulu sebelum search.
4. **Keseragaman pesan error** — untuk mencegah enumerasi NIM, pesan error sebaiknya generik/seragam terlepas dari field mana yang salah (NIM salah, nama salah, atau keduanya), bukan pesan yang berbeda per kasus.
5. **Validasi format NIM** — saat ini validasi hanya mengecek field tidak kosong. Kalau format NIM konsisten (misal panjang/pola digit tertentu), validasi format di awal bisa jadi lapis pertahanan tambahan yang murah.
6. **Test case normalisasi input** — belum ada test eksplisit untuk memastikan nama dengan spasi ganda/kapitalisasi campur tetap match setelah normalisasi.
