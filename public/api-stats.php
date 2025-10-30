<?php
// Pastikan respons JSON tidak tercampur error PHP
ini_set('display_errors', 0);
error_reporting(0);
// Menggunakan konfigurasi dari config.php
require_once __DIR__ . '/../app/config.php';

// Definisikan BASE_PATH jika belum didefinisikan
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Autoload untuk model dan controller
spl_autoload_register(function ($class) {
    $root = BASE_PATH;
    $path = str_replace('\\', '/', $class) . '.php';
    $file = $root . '/' . $path;
    if (file_exists($file)) {
        require $file;
        return;
    }
    // Fallback untuk server Linux yang case-sensitive: App -> app
    $alt = $root . '/' . preg_replace('#^App/#', 'app/', $path);
    if (file_exists($alt)) {
        require $alt;
        return;
    }
});

// Inisialisasi controller dengan guard
try {
    $statsController = new App\Controllers\Admin\StatsController();
} catch (\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['error' => 'Init failed: ' . $e->getMessage()]);
    exit;
}

// Menambahkan pengunjung jika parameter record=visit
if (isset($_GET['record']) && $_GET['record'] === 'visit') {
    $statsController->recordVisit();
}

// Menambahkan aktivitas jika parameter record=activity
if (isset($_GET['record']) && $_GET['record'] === 'activity') {
    $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
    $statsController->recordActivity($count);
}

// Mengembalikan data statistik dengan guard
try {
    header('Content-Type: application/json');
    http_response_code(200);
    echo $statsController->getDashboardStats();
} catch (\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['error' => 'Stats failed: ' . $e->getMessage()]);
}