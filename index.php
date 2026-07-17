<?php

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/NewsController.php';
require_once __DIR__ . '/controllers/FileController.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/AdvisorController.php';

$router = new Router();

$authController = new AuthController();

$router->addRoute('GET', '/login', [$authController, 'showLoginForm']);
$router->addRoute('POST', '/login', [$authController, 'login']);
$router->addRoute('GET', '/logout', [$authController, 'logout']);

$dashboardController = new DashboardController();
// REVISI: Rute dashboard disesuaikan menjadi /admin/dashboard agar sinkron dengan layout
$router->addRoute('GET', '/admin/dashboard', [$dashboardController, 'index']);

$router->addRoute('GET', '/test', function () {
    echo "Router berhasil jalan!";
});

$newsController = new NewsController();

$router->addRoute('GET', '/admin/news', function() use ($newsController) {
    $newsController->listAdmin();
});
$router->addRoute('GET', '/admin/news/create', function() use ($newsController) {
    $newsController->createForm();
});
$router->addRoute('POST', '/admin/news/store', function() use ($newsController) {
    $newsController->store();
});
$router->addRoute('GET', '/admin/news/edit', function() use ($newsController) {
    $id = $_GET['id'] ?? null;
    $newsController->editForm($id);
});
$router->addRoute('POST', '/admin/news/update', function() use ($newsController) {
    $newsController->update();
});
$router->addRoute('POST', '/admin/news/delete', function() use ($newsController) {
    $id = $_POST['id'] ?? null;
    $newsController->delete($id);
});

// Detail berita publik — menggantikan HomeController::showNews() yang sudah dihapus
$router->addRoute('GET', '/berita/{slug}', [$newsController, 'show']);

$fileController = new FileController();

$router->addRoute('GET', '/admin/files', [$fileController, 'listAdmin']);
$router->addRoute('POST', '/admin/files/upload', [$fileController, 'store']);
$router->addRoute('POST', '/admin/files/delete', [$fileController, 'delete']);

$pageController = new PageController();

// REVISI: Menambahkan rute utama untuk menampilkan daftar halaman
$router->addRoute('GET', '/admin/pages', [$pageController, 'listAdmin']);

$router->addRoute('GET', '/admin/pages/edit/{identifier}', [$pageController, 'editForm']);
$router->addRoute('POST', '/admin/pages/save/{identifier}', [$pageController, 'save']);
// Halaman SOP publik — menggantikan HomeController::showPage() yang sudah dihapus
$router->addRoute('GET', '/halaman/{identifier}', [$pageController, 'show']);

$advisorController = new AdvisorController();

$router->addRoute('GET', '/pencarian-dosen', [$advisorController, 'showSearchPage']);
$router->addRoute('POST', '/api/advisors/search', [$advisorController, 'search']);
$router->addRoute('GET', '/admin/import-csv', [$advisorController, 'importCsvForm']);
$router->addRoute('POST', '/admin/import-csv', [$advisorController, 'processImport']);

$homeController = new HomeController();
$router->addRoute('GET', '/', [$homeController, 'index']);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = str_replace('/BAAK-PolNest', '', $requestUri);

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);