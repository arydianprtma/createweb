<?php

namespace App\Models;

class AdminProfile
{
    private $db;

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once _DIR_ . '/../config.php';
        
        // Nonaktifkan pelaporan error mysqli untuk mencegah exception
        \mysqli_report(MYSQLI_REPORT_OFF);
        
        // Koneksi ke database
        $this->db = @\mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$this->db) {
            $this->db = null;
        }
    }

    public function getByUserId($userId)
    {
        if ($this->db === null) return null;
        $userId = \mysqli_real_escape_string($this->db, (string)$userId);
        $query = "SELECT * FROM admin_profiles WHERE user_id = '$userId' LIMIT 1";
        $result = \mysqli_query($this->db, $query);
        return $result ? \mysqli_fetch_assoc($result) : null;
    }

    public function saveProfile($userId, $data)
    {
        if ($this->db === null) return false;
        $userId = \mysqli_real_escape_string($this->db, (string)$userId);
        $full_name = \mysqli_real_escape_string($this->db, $data['full_name'] ?? '');
        $phone = \mysqli_real_escape_string($this->db, $data['phone'] ?? '');
        $address = \mysqli_real_escape_string($this->db, $data['address'] ?? '');
        $website = \mysqli_real_escape_string($this->db, $data['website'] ?? '');
        $avatar_url = \mysqli_real_escape_string($this->db, $data['avatar_url'] ?? '');
        $bio = \mysqli_real_escape_string($this->db, $data['bio'] ?? '');

        // Cek apakah profil sudah ada
        $check = \mysqli_query($this->db, "SELECT id FROM admin_profiles WHERE user_id = '$userId' LIMIT 1");
        if ($check && \mysqli_num_rows($check) > 0) {
            $query = "UPDATE admin_profiles SET full_name='$full_name', phone='$phone', address='$address', website='$website', avatar_url='$avatar_url', bio='$bio', updated_at=NOW() WHERE user_id='$userId'";
        } else {
            $query = "INSERT INTO admin_profiles (user_id, full_name, phone, address, website, avatar_url, bio, created_at, updated_at) VALUES ('$userId', '$full_name', '$phone', '$address', '$website', '$avatar_url', '$bio', NOW(), NOW())";
        }
        
        return \mysqli_query($this->db, $query);
    }
}