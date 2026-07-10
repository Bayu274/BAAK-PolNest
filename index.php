<?php
session_start(); 
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/models/Admin.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/PageController.php';

$router = new Router();

$authController = new AuthController();

$router->addRoute('GET', '/login', [$authController, 'showLoginForm']);
$router->addRoute('POST', '/login', [$authController, 'login']);
$router->addRoute('GET', '/logout', [$authController, 'logout']);

$dashboardController = new DashboardController();
$router->addRoute('GET', '/dashboard', [$dashboardController, 'index']);

$pageController = new PageController();
$router->addRoute('GET', '/admin/pages/edit/sop-cuti', function() use ($pageController) {
    $pageController->editForm('sop-cuti');
});
$router->addRoute('POST', '/admin/pages/save/sop-cuti', function() use ($pageController) {
    $pageController->save('sop-cuti');
});
$router->addRoute('GET', '/pages/sop-cuti', function() use ($pageController) {
    $pageController->show('sop-cuti');
});

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = str_replace('/BAAK-PolNest', '', $requestUri);

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);