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

$router = new Router();

$authController = new AuthController();

$router->addRoute('GET', '/login', [$authController, 'showLoginForm']);
$router->addRoute('POST', '/login', [$authController, 'login']);
$router->addRoute('GET', '/logout', [$authController, 'logout']);

$dashboardController = new DashboardController();
$router->addRoute('GET', '/dashboard', [$dashboardController, 'index']);

$router->addRoute('GET', '/test', function () {
    echo "Router berhasil jalan!";
});

$newsController = new NewsController();
$router->addRoute('GET', '/admin/news', function() use ($newsController) {
    $newsController->listAdmin();
});
$router->addRoute('GET', '/admin/news/create', function() {
    require_once __DIR__ . '/views/backend/news-form.php';
});
$router->addRoute('POST', '/admin/news/store', function() use ($newsController) {
    $newsController->store();
});
$router->addRoute('GET', '/admin/news/create', function() {
    require_once __DIR__ . '/views/backend/news-form.php';
});
// Rute untuk menampilkan form edit
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

$fileController = new FileController();

$router->addRoute('GET', '/admin/files', [$fileController, 'listAdmin']);
$router->addRoute('POST', '/admin/files/upload', [$fileController, 'store']);
$router->addRoute('POST', '/admin/files/delete', [$fileController, 'delete']);

$homeController = new HomeController();
// Rute Beranda
$router->addRoute('GET', '/', function() use ($homeController) {
    $homeController->index();
});

// Rute Detail Berita
$router->addRoute('GET', '/news', function() use ($homeController) {
    $slug = $_GET['slug'] ?? '';
    
    if (empty($slug)) {
         echo "Silakan pilih berita terlebih dahulu dari halaman Beranda.";
         return;
    }
    
    $homeController->showNews($slug);
});
// Rute Detail Halaman Statis (SOP, Profil, dll)
$router->addRoute('GET', '/page', function() use ($homeController) {
    $identifier = $_GET['id'] ?? '';
    $homeController->showPage($identifier);
});

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = str_replace('/BAAK-PolNest', '', $requestUri);

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);