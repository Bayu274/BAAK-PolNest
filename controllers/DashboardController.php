<?php

require_once __DIR__ . '/../models/News.php';
require_once __DIR__ . '/../models/DownloadableFile.php';
require_once __DIR__ . '/../models/Advisor.php';
require_once __DIR__ . '/../models/Page.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $db = getDbConnection();

        $stats = [
            'news'     => (int) $db->query("SELECT COUNT(*) FROM news")->fetchColumn(),
            'files'    => (int) $db->query("SELECT COUNT(*) FROM downloadable_files WHERE is_active = 1")->fetchColumn(),
            'advisors' => (int) $db->query("SELECT COUNT(DISTINCT nim) FROM student_advisors")->fetchColumn(),
            'pages'    => (int) $db->query("SELECT COUNT(*) FROM pages_content")->fetchColumn(),
        ];

        $recentNews = $db->query("SELECT title, created_at FROM news ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('backend/dashboard', [
            'stats'      => $stats,
            'recentNews' => $recentNews,
        ], true);
    }
}
