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
        $thumbnail_image = null; // Ubah nama variabel agar konsisten

        // 2. Proses upload file jika ada (Sesuaikan dengan name="thumbnail_image" di form)
        if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] == 0) {
            $thumbnail_image = 'news_' . time() . '_' . $_FILES['thumbnail_image']['name'];
            move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], __DIR__ . '/../assets/uploads/' . $thumbnail_image);
        }

        // 3. Simpan ke database melalui model
        $this->newsModel->create([
            'title'           => $title,
            'slug'            => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))),
            'content'         => $content,
            'thumbnail_image' => $thumbnail_image, // Samakan key-nya dengan field di database
            'created_by'      => $_SESSION['admin_id'] ?? 1
        ]);

        // 4. Redirect kembali ke daftar berita
        header("Location: /admin/news");
        exit(); 
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
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    // 1. Ambil data lama dari database terlebih dahulu
    $oldNews = $this->newsModel->getById($id); 
    $thumbnail_image = $oldNews['thumbnail_image']; // Set default ke gambar lama

    // 2. Cek apakah ada file gambar BARU yang diupload
    if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] == 0) {
        $thumbnail_image = 'news_' . time() . '_' . $_FILES['thumbnail_image']['name'];
        move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], __DIR__ . '/../assets/uploads/' . $thumbnail_image);
        
        // Opsional: Hapus file gambar lama agar tidak menuhin storage
        if (!empty($oldNews['thumbnail_image']) && file_exists(__DIR__ . '/../assets/uploads/' . $oldNews['thumbnail_image'])) {
            unlink(__DIR__ . '/../assets/uploads/' . $oldNews['thumbnail_image']);
        }
    }

    // 3. Kirim ke model
    $this->newsModel->update([
        'id'              => $id,
        'title'           => $title,
        'slug'            => $slug,
        'content'         => $content,
        'thumbnail_image' => $thumbnail_image
    ]);

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