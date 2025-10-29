<?php
declare(strict_types=1);

// Definisikan BASE_PATH jika belum didefinisikan
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(_DIR_));
}

// Autoloader sederhana
spl_autoload_register(function ($class) {
    // Hapus namespace App\ dari awal class name
    $class = str_replace('App\\', '', $class);
    $baseDir = BASE_PATH . '/app/';
    $path = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

// Router sederhana (tahan banting jika REQUEST_URI tidak tersedia)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
// Jika parse_url gagal (mengembalikan false/null), gunakan root
if ($parsedPath === false || $parsedPath === null) {
    $parsedPath = '/';
}
$uri = rtrim($parsedPath, '/');

// Start session for admin authentication
session_start();

// Set timezone global ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Layani berkas statis di bawah /assets ketika server tidak menyajikan otomatis
if (strpos($uri, '/assets/') === 0) {
    $file = BASE_PATH . '/public' . $uri;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'ico' => 'image/x-icon'
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// Layani tracker visitor sebagai endpoint khusus agar tidak terkena 404 dari router
if ($uri === '/tracker.php' || $uri === '/tracker') {
    require BASE_PATH . '/public/tracker.php';
    exit;
}

if ($uri === '' || $uri === '/') {
    $controller = new App\Controllers\HomeController();
    $controller->index();
    exit;
} elseif ($uri === '/layanan' || $uri === '/services') {
    $controller = new App\Controllers\ServiceController();
    $controller->index();
    exit;
} elseif ($uri === '/kontak' || $uri === '/contact') {
    $controller = new App\Controllers\ContactController();
    $controller->index();
    exit;
} elseif ($uri === '/kontak/kirim' || $uri === '/contact/send') {
    $controller = new App\Controllers\ContactController();
    $controller->send();
    exit;
} 
// Order routes (publik)
elseif ($uri === '/order') {
    $controller = new App\Controllers\OrderController();
    $controller->index();
    exit;
} elseif ($uri === '/order/create') {
    $controller = new App\Controllers\OrderController();
    $controller->create();
    exit;
}
// Admin routes
elseif ($uri === '/admin/login') {
    $controller = new App\Controllers\Admin\AuthController();
    $controller->login();
    exit;
} elseif ($uri === '/admin/auth') {
    $controller = new App\Controllers\Admin\AuthController();
    $controller->auth();
    exit;
} elseif ($uri === '/admin/logout') {
    $controller = new App\Controllers\Admin\AuthController();
    $controller->logout();
    exit;
} elseif ($uri === '/admin' || $uri === '/admin/dashboard') {
    $controller = new App\Controllers\Admin\DashboardController();
    $controller->index();
    exit;
} elseif ($uri === '/admin/services') {
    $controller = new App\Controllers\Admin\ServiceController();
    $controller->index();
    exit;
} elseif ($uri === '/admin/services/new') {
    $controller = new App\Controllers\Admin\ServiceController();
    $controller->new();
    exit;
} elseif (preg_match('/^\/admin\/services\/edit\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
    $controller = new App\Controllers\Admin\ServiceController();
    $controller->edit($matches[1]);
    exit;
} elseif ($uri === '/admin/services/update') {
    $controller = new App\Controllers\Admin\ServiceController();
    $controller->update();
    exit;
} elseif ($uri === '/admin/services/create') {
    $controller = new App\Controllers\Admin\ServiceController();
    $controller->create();
    exit;
}
// Admin Orders
elseif ($uri === '/admin/orders') {
    $controller = new App\Controllers\Admin\OrdersController();
    $controller->index();
    exit;
} elseif (preg_match('/^\/admin\/orders\/view\/(\d+)$/', $uri, $m)) {
    $controller = new App\Controllers\Admin\OrdersController();
    $controller->show((int)$m[1]);
    exit;
} elseif ($uri === '/admin/orders/update-status') {
    $controller = new App\Controllers\Admin\OrdersController();
    $controller->updateStatus();
    exit;
} elseif ($uri === '/admin/orders/update-payment-status') {
    $controller = new App\Controllers\Admin\OrdersController();
    $controller->updatePaymentStatus();
    exit;
}
// Routing untuk pesan customer
elseif ($uri === '/admin/messages') {
    $controller = new App\Controllers\Admin\MessageController();
    $controller->index();
    exit;
} elseif (preg_match('/^\/admin\/messages\/view\/(\d+)$/', $uri, $matches)) {
    $controller = new App\Controllers\Admin\MessageController();
    $controller->view($matches[1]);
    exit;
} elseif ($uri === '/admin/messages/reply') {
    $controller = new App\Controllers\Admin\MessageController();
    $controller->reply();
    exit;
}
// Routing untuk notifikasi
elseif ($uri === '/admin/notifications/unread') {
    $controller = new App\Controllers\Admin\NotificationController();
    $controller->getUnread();
    exit;
} elseif ($uri === '/admin/notifications/mark-read') {
    $controller = new App\Controllers\Admin\NotificationController();
    $controller->markAsRead();
    exit;
} elseif ($uri === '/admin/notifications/mark-all-read') {
    $controller = new App\Controllers\Admin\NotificationController();
    $controller->markAllAsRead();
    exit;
} elseif ($uri === '/admin/notifications/unread-count') {
    header('Content-Type: application/json');
    $controller = new App\Controllers\Admin\NotificationController();
    $count = $controller->getUnreadCount();
    echo json_encode(['count' => $count]);
    exit;
} elseif ($uri === '/admin/settings/general') {
    $controller = new App\Controllers\Admin\SettingsController();
    $controller->general();
    exit;
}
// Routing untuk akun admin
elseif ($uri === '/admin/account/profile') {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->profile();
    exit;
} elseif ($uri === '/admin/account/profile/update') {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->updateProfile();
    exit;
} elseif ($uri === '/admin/account/employees') {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->employees();
    exit;
} elseif ($uri === '/admin/account/employees/create') {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->createEmployee();
    exit;
} elseif (preg_match('/^\/admin\/account\/employees\/edit\/(\d+)$/', $uri, $m)) {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->editEmployee((int)$m[1]);
    exit;
} elseif ($uri === '/admin/account/employees/update') {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->updateEmployee();
    exit;
} elseif (preg_match('/^\/admin\/account\/employees\/delete\/(\d+)$/', $uri, $m)) {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->deleteEmployee((int)$m[1]);
    exit;
} elseif (preg_match('/^\/admin\/account\/employees\/activate\/(\d+)$/', $uri, $m)) {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->activateEmployee((int)$m[1]);
    exit;
} elseif (preg_match('/^\/admin\/account\/employees\/deactivate\/(\d+)$/', $uri, $m)) {
    $controller = new App\Controllers\Admin\AccountController();
    $controller->deactivateEmployee((int)$m[1]);
    exit;
}
// Pengaturan Tampilan dihapus

http_response_code(404);
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404</title></head><body style="font-family:system-ui,sans-serif"><h1>404 Not Found</h1><p>Halaman tidak ditemukan.</p></body></html>';