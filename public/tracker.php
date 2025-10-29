<?php
// Konfigurasi header untuk CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Menggunakan konfigurasi dari config.php
require_once __DIR__ . '/../app/config.php';

try {
    // Pastikan error PHP tidak mengganggu output JSON
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    
    // Daftar kredensial yang akan dicoba
    $credentials = [
        [$db_host, $db_user, $db_pass, $db_name],
    ];
    
    $connected = false;
    $last_error = "";
    
    // Coba semua kombinasi kredensial
    foreach ($credentials as $cred) {
        $db = @new mysqli($cred[0], $cred[1], $cred[2], $cred[3]);
        if (!$db->connect_error) {
            $connected = true;
            break;
        } else {
            $last_error = $db->connect_error;
        }
    }
    
    // Jika semua koneksi gagal
    if (!$connected) {
        throw new Exception("Connection failed: " . $last_error);
    }
    
    // Ambil data pengunjung
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    $timestamp = date('Y-m-d H:i:s');
    
    // Ambil data dari request POST JSON
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    $page = $data['page'] ?? ($_GET['page'] ?? 'unknown');
    
    // Tambah pengunjung langsung ke database
    $today = date('Y-m-d');
    $result = $db->query("SELECT id, count FROM visitors WHERE visit_date = '$today'");
    
    if ($result->num_rows > 0) {
        // Update data yang sudah ada
        $row = $result->fetch_assoc();
        $newCount = $row['count'] + 1;
        $id = $row['id'];
        $visitorResult = $db->query("UPDATE visitors SET count = $newCount WHERE id = $id");
    } else {
        // Tambah data baru
        $visitorResult = $db->query("INSERT INTO visitors (visit_date, count) VALUES ('$today', 1)");
    }
    
    // Tambah aktivitas bulanan
    $month = date('n');
    $year = date('Y');
    $result = $db->query("SELECT id, activity_count FROM monthly_activities WHERE month = $month AND year = $year");
    
    if ($result->num_rows > 0) {
        // Update data yang sudah ada
        $row = $result->fetch_assoc();
        $newCount = $row['activity_count'] + 1;
        $id = $row['id'];
        $activityResult = $db->query("UPDATE monthly_activities SET activity_count = $newCount WHERE id = $id");
    } else {
        // Tambah data baru
        $activityResult = $db->query("INSERT INTO monthly_activities (month, year, activity_count) VALUES ($month, $year, 1)");
    }
    
    // Tutup koneksi
    $db->close();
    
    // Kembalikan respons
    echo json_encode([
        'status' => 'success',
        'visitor_added' => $visitorResult ? true : false,
        'activity_added' => $activityResult ? true : false,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log('Error tracking visitor: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}