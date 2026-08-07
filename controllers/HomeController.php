<?php

if (!defined('BASE_URL')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/../models/News.php';
require_once __DIR__ . '/../models/DownloadableFile.php';

class HomeController extends Controller {

    private News $newsModel;

    public function __construct() {
        $this->newsModel = new News();
    }

    public function index() {
        $latestNews = $this->newsModel->getAll(6, null, true);
        $this->render('frontend/home', ['latestNews' => $latestNews], 'frontend');
    }

    public function showJadwal() {
        $fileModel = new DownloadableFile();
        $files = $fileModel->getActiveFiles();
        $this->render('frontend/jadwal', [
            'pageTitle' => 'Jadwal & Pedoman BAAK',
            'files' => $files
        ], 'frontend');
    }
}
