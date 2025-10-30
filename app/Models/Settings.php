<?php

namespace App\Models;

class Settings
{
    private $db;
    
    // Pastikan tabel settings tersedia
    private function ensureSchema(): void
    {
        if ($this->db === null) { return; }
        $sql = "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `setting_key` VARCHAR(100) NOT NULL,
            `setting_value` TEXT NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($this->db, $sql);
    }

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once __DIR__ . '/../config.php';
        
        // Nonaktifkan pelaporan error mysqli untuk mencegah exception
        mysqli_report(MYSQLI_REPORT_OFF);
        
        // Koneksi ke database
        $this->db = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Jika koneksi gagal
        if (!$this->db) {
            // Tampilkan pesan error yang informatif
            echo "Koneksi database gagal: " . mysqli_connect_error();
            // Gunakan fallback untuk mencegah aplikasi berhenti total
            $this->db = null;
        }
    }

    public function getSettings($key)
    {
        // Cek apakah koneksi database tersedia
        if ($this->db === null) {
            return null;
        }
        // Pastikan tabel ada
        $this->ensureSchema();
        
        $key = mysqli_real_escape_string($this->db, $key);
        $query = "SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1";
        $result = mysqli_query($this->db, $query);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return json_decode($row['setting_value'], true);
        }
        
        return null;
    }

    public function saveSettings($key, $data)
    {
        // Cek apakah koneksi database tersedia
        if ($this->db === null) {
            return false;
        }
        // Pastikan tabel ada
        $this->ensureSchema();
        
        $key = mysqli_real_escape_string($this->db, $key);
        $value = json_encode($data);
        $value = mysqli_real_escape_string($this->db, $value);
        
        // Cek apakah setting sudah ada
        $query = "SELECT id FROM settings WHERE setting_key = '$key'";
        $result = mysqli_query($this->db, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            // Update
            $query = "UPDATE settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
        } else {
            // Insert
            $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
        }
        
        return mysqli_query($this->db, $query);
    }
}