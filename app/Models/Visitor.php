<?php

namespace App\Models;

class Visitor {
    private $db;

    public function __construct() {
        // Gunakan pola koneksi yang aman dan non-fatal seperti model lain
        require_once __DIR__ . '/../config.php';
        \mysqli_report(MYSQLI_REPORT_OFF);
        $this->db = @\mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db) {
            // Biarkan null agar metode bisa memberi nilai default tanpa mematikan skrip
            $this->db = null;
        }
    }

    // Mendapatkan total pengunjung
    public function getTotalVisitors() {
        if (!$this->db) { return 0; }
        $query = "SELECT SUM(count) as total FROM visitors";
        $result = $this->db->query($query);
        if (!$result) { return 0; }
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    // Menambahkan pengunjung baru
    public function addVisitor() {
        if (!$this->db) { return false; }
        $today = date('Y-m-d');
        
        // Cek apakah sudah ada data untuk hari ini
        $query = "SELECT id, count FROM visitors WHERE visit_date = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update data yang sudah ada
            $row = $result->fetch_assoc();
            $newCount = $row['count'] + 1;
            $id = $row['id'];
            
            $updateQuery = "UPDATE visitors SET count = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bind_param("ii", $newCount, $id);
            return $updateStmt->execute();
        } else {
            // Tambah data baru
            $insertQuery = "INSERT INTO visitors (visit_date, count) VALUES (?, 1)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("s", $today);
            return $insertStmt->execute();
        }
    }

    // Mendapatkan data pengunjung 30 hari terakhir
    public function getRecentVisitors($days = 30) {
        if (!$this->db) { return []; }
        $query = "SELECT visit_date, count FROM visitors 
                 WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
                 ORDER BY visit_date ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }

    // Mendapatkan persentase perubahan pengunjung dari bulan lalu
    public function getVisitorChangePercentage() {
        if (!$this->db) { return 0.0; }
        $currentMonthQuery = "SELECT SUM(count) as total FROM visitors 
                             WHERE MONTH(visit_date) = MONTH(CURDATE()) 
                             AND YEAR(visit_date) = YEAR(CURDATE())";
        $currentResult = $this->db->query($currentMonthQuery);
        $currentTotal = $currentResult ? ($currentResult->fetch_assoc()['total'] ?? 0) : 0;

        $lastMonthQuery = "SELECT SUM(count) as total FROM visitors 
                          WHERE MONTH(visit_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                          AND YEAR(visit_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        $lastResult = $this->db->query($lastMonthQuery);
        $lastTotal = $lastResult ? ($lastResult->fetch_assoc()['total'] ?? 0) : 0;

        // Penanganan kasus khusus agar tidak selalu 0%
        if ($lastTotal > 0) {
            $percentage = (($currentTotal - $lastTotal) / $lastTotal) * 100;
            return round($percentage, 1);
        }
        // Jika bulan lalu 0 dan sekarang ada data, tampilkan 100%
        if ($lastTotal == 0 && $currentTotal > 0) {
            return 100.0;
        }
        // Jika keduanya 0, tetap 0%
        return 0.0;
    }

    public function __destruct() {
        if ($this->db !== null) {
            $this->db->close();
        }
    }
}