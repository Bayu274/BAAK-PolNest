<?php
class NewsController extends Controller {
    private $newsModel;
    public function __construct() {
        require_once __DIR__ . '/../models/News.php';
        $this->newsModel = new News();
    }

    public function listAdmin() {
        $newsList = $this->newsModel->getAll() ?? []; 
        $this->render('backend/news-list', ['newsList' => $newsList]);
    }

    public function store() {
        // 1. Ambil data dari form
        $title = $_POST['title'];
        $content = $_POST['content'];
        $thumbnail = null;

        // 2. Proses upload file jika ada
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $thumbnail = 'news_' . time() . '_' . $_FILES['thumbnail']['name'];
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], __DIR__ . '/../assets/uploads/' . $thumbnail);
        }

        // 3. Simpan ke database melalui model
        $this->newsModel->create([
            'title'      => $title,
            'slug'       => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))),
            'content'    => $content,
            'thumbnail'  => $thumbnail,
            'created_by' => $_SESSION['admin_id'] ?? 1 // Pastikan ID 1 ada di tabel admin_users
        ]);

        // 4. Redirect kembali ke daftar berita
        header("Location: /admin/news");
        exit(); // Tambahkan exit() agar script berhenti setelah redirect
    }
    // Tambahkan di dalam NewsController.php
    public function editForm($id) {
    if (!$id) {
        header("Location: /admin/news");
        return;
    }
    
    // Ambil data berita dari model
    $news = $this->newsModel->getById($id); // Kamu perlu membuat fungsi getById di Model
    if (!$news) {
        die("Berita tidak ditemukan.");
    }
    
    $this->render('backend/news-form', ['news' => $news, 'isEdit' => true]);
    }
    public function update() {
    // 1. Ambil data dari form
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    $thumbnail = null;

    // 2. Cek apakah ada file gambar BARU yang diupload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $thumbnail = 'news_' . time() . '_' . $_FILES['thumbnail']['name'];
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], __DIR__ . '/../assets/uploads/' . $thumbnail);
    }

    // 3. Kirim data ke model untuk di-update
    $this->newsModel->update([
        'id'        => $id,
        'title'     => $title,
        'slug'      => $slug,
        'content'   => $content,
        'thumbnail' => $thumbnail // Ini akan bernilai nama file baru, atau null jika tidak ada gambar baru
    ]);

    // 4. Kembali ke halaman list berita
    header("Location: /admin/news");
    exit();
}

    public function delete($id) {
        if (!$id) {
            header("Location: /admin/news");
            return;
        }

        // Panggil model untuk menghapus berita
        $this->newsModel->delete($id);

        // Redirect kembali ke daftar berita
        header("Location: /admin/news");
        exit();
    }
}