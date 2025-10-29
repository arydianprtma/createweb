<?php
// Menggunakan konfigurasi dari config.php
require_once __DIR__ . '/../app/config.php';

// Definisikan BASE_PATH jika belum didefinisikan
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Autoload untuk model dan controller
spl_autoload_register(function ($class) {
    $root = BASE_PATH;
    $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Inisialisasi controller
$statsController = new App\Controllers\Admin\StatsController();

// Menambahkan pengunjung jika parameter record=visit
if (isset($_GET['record']) && $_GET['record'] === 'visit') {
    $statsController->recordVisit();
}

// Menambahkan aktivitas jika parameter record=activity
if (isset($_GET['record']) && $_GET['record'] === 'activity') {
    $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
    $statsController->recordActivity($count);
}

// Mengembalikan data statistik
header('Content-Type: application/json');
echo $statsController->getDashboardStats();