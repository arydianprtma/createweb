<?php

namespace App\Models;

class MonthlyActivity {
    private $db;

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once __DIR__ . '/../config.php';
        
        // Nonaktifkan pelaporan error mysqli untuk mencegah exception
        mysqli_report(MYSQLI_REPORT_OFF);
        
        // Koneksi ke database (non-fatal)
        $this->db = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->db) {
            // Fallback: biarkan null, metode akan mengembalikan nilai default
            $this->db = null;
        }
    }

    // Mendapatkan data aktivitas bulanan untuk 12 bulan terakhir
    public function getMonthlyActivities() {
        $data = [];
        
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return $data;
        }
        
        // Ambil 12 bulan terakhir secara inklusif, berakhir di bulan berjalan
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');
        $endKey = ($currentYear * 100) + $currentMonth;
        $startKey = (int)date('Ym', strtotime('-11 months'));

        $query = "SELECT month, year, activity_count FROM monthly_activities 
                 WHERE (year * 100 + month) BETWEEN $startKey AND $endKey
                 ORDER BY year, month ASC";
        $result = $this->db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    // Mendapatkan data aktivitas bulanan dalam format array sederhana
    public function getMonthlyActivityData() {
        $data = array_fill(0, 12, 0); // Inisialisasi array dengan 12 bulan
        
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return $data;
        }
        
        $activities = $this->getMonthlyActivities();
        
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        foreach ($activities as $activity) {
            $month = $activity['month'];
            $year = $activity['year'];
            $count = $activity['activity_count'];
            
            // Hitung posisi dalam array (0-11)
            $monthDiff = (($currentYear - $year) * 12) + ($currentMonth - $month);
            $index = 11 - $monthDiff;
            
            if ($index >= 0 && $index < 12) {
                $data[$index] = $count;
            }
        }
        
        return $data;
    }

    // Menambah aktivitas untuk bulan ini
    public function incrementActivity($count = 1) {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return false;
        }
        
        $month = date('n');
        $year = date('Y');
        
        // Cek apakah sudah ada data untuk bulan ini
        $query = "SELECT id, activity_count FROM monthly_activities WHERE month = ? AND year = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            // Update data yang sudah ada
            $row = $result->fetch_assoc();
            $newCount = $row['activity_count'] + $count;
            $id = $row['id'];
            
            $updateQuery = "UPDATE monthly_activities SET activity_count = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            if (!$updateStmt) {
                return false;
            }
            
            $updateStmt->bind_param("ii", $newCount, $id);
            return $updateStmt->execute();
        } else {
            // Tambah data baru
            $insertQuery = "INSERT INTO monthly_activities (month, year, activity_count) VALUES (?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            if (!$insertStmt) {
                return false;
            }
            
            $insertStmt->bind_param("iii", $month, $year, $count);
            return $insertStmt->execute();
        }
    }

    public function __destruct() {
        if ($this->db !== null) {
            $this->db->close();
        }
    }
}