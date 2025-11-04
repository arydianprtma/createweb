<?php

namespace App\Models;

class Order {
    private $db;

    public function __construct() {
        // Muat konfigurasi database dan koneksi aman seperti model lain
        require_once _DIR_ . '/../config.php';
        \mysqli_report(MYSQLI_REPORT_OFF);
        $this->db = @\mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db) {
            // Fallback: biarkan null, metode akan menangani ketidaktersediaan DB
            $this->db = null;
        }
    }

    // Pastikan tabel orders tersedia
    public function ensureSchema(): void
    {
        if ($this->db === null) { return; }
        // Buat tabel jika belum ada (minimal kolom bawaan)
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id INT(11) NOT NULL AUTO_INCREMENT,
            customer_name VARCHAR(100) NOT NULL,
            service_name VARCHAR(100) NOT NULL,
            order_date DATE NOT NULL,
            status ENUM('pending','process','completed') NOT NULL DEFAULT 'pending',
            amount DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @mysqli_query($this->db, $sql);

        // Tambahkan kolom tambahan yang diperlukan aplikasi jika belum ada
        $columnsToAdd = [
            'order_code' => "ALTER TABLE orders ADD COLUMN order_code VARCHAR(16) NOT NULL DEFAULT '' AFTER id",
            'customer_email' => "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(100) NOT NULL AFTER customer_name",
            'whatsapp' => "ALTER TABLE orders ADD COLUMN whatsapp VARCHAR(20) NOT NULL AFTER customer_email",
            'service_code' => "ALTER TABLE orders ADD COLUMN service_code VARCHAR(50) NOT NULL AFTER whatsapp",
            'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','dp','paid','cancelled') NOT NULL DEFAULT 'pending' AFTER status"
        ];
        foreach ($columnsToAdd as $col => $alterSql) {
            $check = @mysqli_query($this->db, "SHOW COLUMNS FROM orders LIKE '$col'");
            if ($check && mysqli_num_rows($check) === 0) {
                @mysqli_query($this->db, $alterSql);
            }
        }

        // Pastikan enum status & payment_status mendukung nilai baru
        if ($this->db) {
            // Perluas enum kolom status jika belum berisi status baru
            $colInfoStatus = @mysqli_query($this->db, "SHOW COLUMNS FROM orders LIKE 'status'");
            if ($colInfoStatus && ($infoS = mysqli_fetch_assoc($colInfoStatus)) && isset($infoS['Type'])) {
                $needAlterStatus = false;
                $requiredStatus = [
                    'pending','process','completed',
                    'draft','waiting_payment','design_phase','revision','development','testing','deployment','maintenance','cancelled'
                ];
                foreach ($requiredStatus as $st) {
                    if (stripos($infoS['Type'], "'".$st."'") === false) { $needAlterStatus = true; break; }
                }
                if ($needAlterStatus) {
                    @mysqli_query($this->db, "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','process','completed','draft','waiting_payment','design_phase','revision','development','testing','deployment','maintenance','cancelled') NOT NULL DEFAULT 'pending'");
                }
            }

            // Perluas enum payment_status dengan nilai 'dp' jika belum ada
            $colInfo = @mysqli_query($this->db, "SHOW COLUMNS FROM orders LIKE 'payment_status'");
            if ($colInfo && ($info = mysqli_fetch_assoc($colInfo)) && isset($info['Type'])) {
                if (stripos($info['Type'], "'dp'") === false) {
                    @mysqli_query($this->db, "ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','dp','paid','cancelled') NOT NULL DEFAULT 'pending'");
                }
            }
        }
        if ($this->db) {
            $colInfo = @mysqli_query($this->db, "SHOW COLUMNS FROM orders LIKE 'payment_status'");
            if ($colInfo && ($info = mysqli_fetch_assoc($colInfo)) && isset($info['Type'])) {
                if (stripos($info['Type'], "'dp'") === false) {
                    @mysqli_query($this->db, "ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','dp','paid','cancelled') NOT NULL DEFAULT 'pending'");
                }
            }
        }

        // Tambahkan indeks jika belum ada
        $indexChecks = [
            'uniq_order_code' => "ALTER TABLE orders ADD UNIQUE KEY uniq_order_code(order_code)",
            'idx_order_date' => "ALTER TABLE orders ADD INDEX idx_order_date(order_date)",
            'idx_service_code' => "ALTER TABLE orders ADD INDEX idx_service_code(service_code)",
            'idx_payment_status' => "ALTER TABLE orders ADD INDEX idx_payment_status(payment_status)"
        ];
        foreach ($indexChecks as $indexName => $alterIndexSql) {
            $checkIdx = @mysqli_query($this->db, "SHOW INDEX FROM orders WHERE Key_name = '$indexName'");
            if (!$checkIdx || mysqli_num_rows($checkIdx) === 0) {
                @mysqli_query($this->db, $alterIndexSql);
            }
        }

        // Backfill order_code untuk data lama yang belum punya nilai
        @mysqli_query($this->db, "UPDATE orders SET order_code = CONCAT('CW-', LPAD(id, 6, '0')) WHERE (order_code IS NULL OR order_code = '')");
    }

    // Membuat pesanan baru
    public function createOrder(array $data)
    {
        // Validasi koneksi
        if (!$this->db) {
            return false;
        }

        // Pastikan tabel ada
        $this->ensureSchema();

        $customerName = $this->db->real_escape_string($data['customer_name'] ?? '');
        $customerEmail = $this->db->real_escape_string($data['customer_email'] ?? '');
        $whatsapp = $this->db->real_escape_string($data['whatsapp'] ?? '');
        $serviceCode = $this->db->real_escape_string($data['service_code'] ?? '');
        $serviceName = $this->db->real_escape_string($data['service_name'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        $status = $this->db->real_escape_string($data['status'] ?? 'pending');
        // Sesuaikan format order_date dengan tipe kolom di DB (DATE atau DATETIME)
        $orderDateType = 'DATETIME';
        $colInfo = @mysqli_query($this->db, "SHOW COLUMNS FROM orders LIKE 'order_date'");
        if ($colInfo && ($info = mysqli_fetch_assoc($colInfo)) && isset($info['Type'])) {
            $orderDateType = stripos($info['Type'], 'date') !== false && stripos($info['Type'], 'time') === false ? 'DATE' : 'DATETIME';
        }
        $orderDate = $orderDateType === 'DATE' ? date('Y-m-d') : date('Y-m-d H:i:s');

        // Buat kode pesanan unik: CW- + 6 digit
        $orderCode = $this->generateUniqueOrderCode();

        $query = "INSERT INTO orders (order_code, customer_name, customer_email, whatsapp, service_code, service_name, amount, status, order_date) VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssssssdss', $orderCode, $customerName, $customerEmail, $whatsapp, $serviceCode, $serviceName, $amount, $status, $orderDate);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    // Generate kode pesanan unik dengan format CW-XXXXXX
    private function generateUniqueOrderCode(): string
    {
        if (!$this->db) { return 'CW-' . substr((string)mt_rand(100000, 999999), -6); }
        $this->ensureSchema();
        for ($i = 0; $i < 20; $i++) {
            $num = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $code = 'CW-' . $num;
            $codeEsc = $this->db->real_escape_string($code);
            $res = $this->db->query("SELECT id FROM orders WHERE order_code = '$codeEsc' LIMIT 1");
            if ($res && $res->num_rows === 0) {
                return $code;
            }
        }
        // Fallback menggunakan waktu
        return 'CW-' . substr((string)(time() % 1000000), -6);
    }

    // Mendapatkan total pesanan
    public function getTotalOrders() {
        if (!$this->db) { return 0; }
        $query = "SELECT COUNT(*) as total FROM orders";
        $result = $this->db->query($query);
        if (!$result) { return 0; }
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    // Mendapatkan jumlah project selesai berdasarkan status pesanan 'completed'
    public function getCompletedCount(): int {
        if (!$this->db) { return 0; }
        $this->ensureSchema();
        $query = "SELECT COUNT(*) AS total FROM orders WHERE status = 'completed'";
        $result = $this->db->query($query);
        if (!$result) { return 0; }
        $row = $result->fetch_assoc();
        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    // Mendapatkan total pendapatan (hanya dari orders yang sudah dibayar)
    public function getTotalRevenue() {
        if (!$this->db) { return 0.0; }
        $this->ensureSchema();
        $query = "SELECT SUM(amount) as total FROM orders WHERE payment_status = 'paid'";
        $result = $this->db->query($query);
        if (!$result) { return 0.0; }
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0.0);
    }

    // Mendapatkan pesanan terbaru
    public function getRecentOrders($limit = 5) {
        // Pastikan tabel ada
        if (!$this->db) { return []; }
        $this->ensureSchema();
        $query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) { return []; }
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $orders[] = $row;
        }
        
        return $orders;
    }

    // Mengambil satu pesanan berdasarkan ID
    public function getOrderById(int $id)
    {
        if (!$this->db) { return null; }
        $this->ensureSchema();
        $query = "SELECT * FROM orders WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        if (!$stmt) { return null; }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    // Mengambil semua pesanan dengan filter opsional
    public function getAllOrders($filters = [])
    {
        if (!$this->db) { return []; }
        $this->ensureSchema();
        
        $query = "SELECT * FROM orders WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['payment_status'])) {
            $query .= " AND payment_status = ?";
            $params[] = $filters['payment_status'];
            $types .= "s";
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (customer_name LIKE ? OR customer_email LIKE ? OR service_name LIKE ? OR order_code LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }
        
        $query .= " ORDER BY order_date DESC";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) { return []; }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $orders[] = $row;
        }
        
        return $orders;
    }

    // Memperbarui status pesanan
    public function updateStatus(int $id, string $status): bool
    {
        if (!$this->db) { return false; }
        $allowed = [
            'pending','process','completed', // lama
            'draft','waiting_payment','design_phase','revision','development','testing','deployment','maintenance','cancelled' // baru
        ];
        if (!in_array($status, $allowed, true)) { return false; }
        $this->ensureSchema();
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) { return false; }
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    // Memperbarui status pembayaran
    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        if (!$this->db) { return false; }
        $allowed = ['pending','dp','paid','cancelled'];
        if (!in_array($paymentStatus, $allowed, true)) { return false; }
        $this->ensureSchema();
        $query = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) { return false; }
        $stmt->bind_param('si', $paymentStatus, $id);
        return $stmt->execute();
    }

    // Persentase perubahan jumlah pesanan dari bulan lalu
    public function getOrdersChangePercentage() {
        if (!$this->db) { return 0.0; }
        $currentQuery = "SELECT COUNT(*) as total FROM orders 
                         WHERE MONTH(order_date) = MONTH(CURDATE()) 
                         AND YEAR(order_date) = YEAR(CURDATE())";
        $currentResult = $this->db->query($currentQuery);
        $currentTotal = $currentResult ? ($currentResult->fetch_assoc()['total'] ?? 0) : 0;

        $lastQuery = "SELECT COUNT(*) as total FROM orders 
                       WHERE MONTH(order_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                       AND YEAR(order_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        $lastResult = $this->db->query($lastQuery);
        $lastTotal = $lastResult ? ($lastResult->fetch_assoc()['total'] ?? 0) : 0;

        if ($lastTotal > 0) {
            $percentage = (($currentTotal - $lastTotal) / $lastTotal) * 100;
            return round($percentage, 1);
        }
        // Jika bulan lalu 0 dan sekarang ada pesanan, tampilkan 100%
        if ($lastTotal == 0 && $currentTotal > 0) {
            return 100.0;
        }
        return 0.0;
    }

    // Persentase perubahan pendapatan dari bulan lalu (hanya dari orders yang sudah dibayar)
    public function getRevenueChangePercentage() {
        if (!$this->db) { return 0.0; }
        $currentQuery = "SELECT SUM(amount) as total FROM orders 
                         WHERE MONTH(order_date) = MONTH(CURDATE()) 
                         AND YEAR(order_date) = YEAR(CURDATE())
                         AND payment_status = 'paid'";
        $currentResult = $this->db->query($currentQuery);
        $currentTotal = $currentResult ? ($currentResult->fetch_assoc()['total'] ?? 0) : 0;

        $lastQuery = "SELECT SUM(amount) as total FROM orders 
                       WHERE MONTH(order_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                       AND YEAR(order_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                       AND payment_status = 'paid'";
        $lastResult = $this->db->query($lastQuery);
        $lastTotal = $lastResult ? ($lastResult->fetch_assoc()['total'] ?? 0) : 0;

        if ($lastTotal > 0) {
            $percentage = (($currentTotal - $lastTotal) / $lastTotal) * 100;
            return round($percentage, 1);
        }
        // Jika bulan lalu 0 dan sekarang ada pendapatan, tampilkan 100%
        if ($lastTotal == 0 && $currentTotal > 0) {
            return 100.0;
        }
        return 0.0;
    }
}