<?php
class HomeController extends Controller {
    private $newsModel;
    private $pageModel; // Tambahkan properti untuk model Page

    public function __construct() {
        require_once __DIR__ . '/../models/News.php';
        require_once __DIR__ . '/../models/Page.php'; // Load model Page
        
        $this->newsModel = new News();
        $this->pageModel = new Page();
    }

    public function index() {
        $latestNews = $this->newsModel->getAll(6); 
        $this->render('frontend/home', ['latestNews' => $latestNews]);
    }

    public function showNews($slug) {
        $news = $this->newsModel->getBySlug($slug);
        if (!$news) {
            die("Berita tidak ditemukan.");
        }
        $this->render('frontend/news-detail', ['news' => $news]);
    }

    // Tambahkan fungsi baru ini untuk merender SOP / Halaman Statis
    public function showPage($identifier) {
        $page = $this->pageModel->getByIdentifier($identifier);
        
        if (!$page) {
            die("Halaman layanan belum tersedia.");
        }
        
        // Render ke file page-detail.php yang sudah ada
        $this->render('frontend/page-detail', ['page' => $page]);
    }
}