<?php

namespace App\Models;

class UserAdmin
{
    private $db;
    private $aes_key = 'createweb_secure_key_2023'; // Kunci enkripsi AES
    private $aes_method = 'AES-256-CBC'; // Metode enkripsi

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once _DIR_ . '/../config.php';
        $this->db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    
    // Fungsi untuk mengenkripsi password dengan AES
    private function encryptPassword($password)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->aes_method));
        $encrypted = openssl_encrypt($password, $this->aes_method, $this->aes_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    // Fungsi untuk mendekripsi password dengan AES
    private function decryptPassword($encryptedPassword)
    {
        list($encrypted_data, $iv) = explode('::', base64_decode($encryptedPassword), 2);
        return openssl_decrypt($encrypted_data, $this->aes_method, $this->aes_key, 0, $iv);
    }

    public function getAllUsers()
    {
        $query = "SELECT id, full_name, username, role, is_active FROM user_admin ORDER BY created_at DESC";
        $result = mysqli_query($this->db, $query);
        $users = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        
        return $users;
    }

    public function getUserByUsername($username)
    {
        $username = mysqli_real_escape_string($this->db, $username);
        $query = "SELECT * FROM user_admin WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($this->db, $query);
        
        return mysqli_fetch_assoc($result);
    }

    public function verifyPassword($username, $password)
    {
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            return false;
        }
        // Blokir login jika akun non-aktif
        if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
            return false;
        }
        
        $stored = $user['password'] ?? '';
        
        // Jika formatnya hash bcrypt/argon, gunakan password_verify
        if ($this->looksLikePasswordHash($stored)) {
            return password_verify($password, $stored);
        }
        
        // Fallback ke AES dekripsi untuk kompatibilitas lama
        $decryptedPassword = $this->decryptPassword($stored);
        return $decryptedPassword !== false && $decryptedPassword === $password;
    }

    // Deteksi jika string tampak seperti hash password modern (bcrypt/argon)
    private function looksLikePasswordHash($hash)
    {
        if (!is_string($hash)) return false;
        // Bcrypt ($2y$ / $2a$ / $2b$) biasanya panjang 60
        if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
            return true;
        }
        // Argon2i/id
        if (strpos($hash, '$argon2i$') === 0 || strpos($hash, '$argon2id$') === 0) {
            return true;
        }
        return false;
    }

    // Untuk migrasi database
    public function createUserAdminTable()
    {
        // Menggunakan mysqli langsung untuk membuat tabel
        $query = "CREATE TABLE IF NOT EXISTS user_admin (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            full_name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NULL,
            role ENUM('Superadmin','Admin') NOT NULL DEFAULT 'Admin',
            password VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY username (username),
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        mysqli_query($this->db, $query);

        // Insert default data
        $this->insertDefaultUser();
    }

    private function insertDefaultUser()
    {
        $timestamp = date('Y-m-d H:i:s');
        
        $full_name = 'Ary Dian Pratama';
        $username = 'arydianprtma';
        $email = 'arydianprtma@gmail.com';
        $role = 'Superadmin';
        $password = 'REXUSlit-53';
        
        // Hash password dengan bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Cek apakah data sudah ada
        $check_query = "SELECT id FROM user_admin WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($this->db, $check_query);
        
        if (mysqli_num_rows($result) == 0) {
            $hashedPassword = mysqli_real_escape_string($this->db, $hashedPassword);
            $full_name = mysqli_real_escape_string($this->db, $full_name);
            $role = mysqli_real_escape_string($this->db, $role);
            $query = "INSERT INTO user_admin (full_name, username, email, role, password, created_at, updated_at) 
                      VALUES ('$full_name', '$username', '$email', '$role', '$hashedPassword', '$timestamp', '$timestamp')";
            mysqli_query($this->db, $query);
        }
    }

    public function usernameExists($username)
    {
        $username = mysqli_real_escape_string($this->db, $username);
        $sql = "SELECT id FROM user_admin WHERE username = '$username' LIMIT 1";
        $res = mysqli_query($this->db, $sql);
        return $res && mysqli_num_rows($res) > 0;
    }

    public function createUser($fullName, $username, $role, $passwordHash, $email = null)
    {
        $fullName = mysqli_real_escape_string($this->db, $fullName);
        $username = mysqli_real_escape_string($this->db, $username);
        $role = mysqli_real_escape_string($this->db, $role);
        $passwordHash = mysqli_real_escape_string($this->db, $passwordHash);
        $emailValue = $email !== null ? ("'" . mysqli_real_escape_string($this->db, $email) . "'") : 'NULL';
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO user_admin (full_name, username, email, role, password, is_active, created_at, updated_at) 
                VALUES ('$fullName', '$username', $emailValue, '$role', '$passwordHash', 1, '$now', '$now')";
        return mysqli_query($this->db, $sql);
    }

    public function getUserById($id)
    {
        $id = (int)$id;
        $sql = "SELECT * FROM user_admin WHERE id = $id LIMIT 1";
        $res = mysqli_query($this->db, $sql);
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    public function updateUser($id, $fullName, $username, $role, $passwordHash = null)
    {
        $id = (int)$id;
        $fullName = mysqli_real_escape_string($this->db, $fullName);
        $username = mysqli_real_escape_string($this->db, $username);
        $role = mysqli_real_escape_string($this->db, $role);
        $now = date('Y-m-d H:i:s');
        if ($passwordHash) {
            $passwordHash = mysqli_real_escape_string($this->db, $passwordHash);
            $sql = "UPDATE user_admin SET full_name='$fullName', username='$username', role='$role', password='$passwordHash', updated_at='$now' WHERE id=$id";
        } else {
            $sql = "UPDATE user_admin SET full_name='$fullName', username='$username', role='$role', updated_at='$now' WHERE id=$id";
        }
        return mysqli_query($this->db, $sql);
    }

    public function deleteUser($id)
    {
        $id = (int)$id;
        $sql = "DELETE FROM user_admin WHERE id=$id";
        return mysqli_query($this->db, $sql);
    }

    public function setActive($id, $isActive)
    {
        $id = (int)$id;
        $isActive = (int)$isActive;
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE user_admin SET is_active=$isActive, updated_at='$now' WHERE id=$id";
        return mysqli_query($this->db, $sql);
    }

    /**
     * Tracking online admin: pastikan kolom last_seen ada dan utilitas terkait
     */
    public function ensureSchema()
    {
        // Tambahkan kolom last_seen jika belum ada
        $check = mysqli_query($this->db, "SHOW COLUMNS FROM user_admin LIKE 'last_seen'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($this->db, "ALTER TABLE user_admin ADD COLUMN last_seen DATETIME NULL AFTER updated_at");
        }
    }

    public function touchLastSeenByUsername(string $username): bool
    {
        $username = mysqli_real_escape_string($this->db, $username);
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE user_admin SET last_seen='$now' WHERE username='$username'";
        return mysqli_query($this->db, $sql) ? true : false;
    }

    /**
     * Ambil daftar user online berdasarkan ambang menit terakhir aktif (default 5 menit)
     */
    public function getOnlineUsers(int $thresholdMinutes = 5): array
    {
        $thresholdMinutes = max(1, $thresholdMinutes);
        // Menggunakan TIMESTAMPDIFF agar kompatibel tanpa fungsi interval eksplisit
        $sql = "SELECT id, username, role, is_active, last_seen
                FROM user_admin
                WHERE is_active = 1
                  AND last_seen IS NOT NULL
                  AND TIMESTAMPDIFF(MINUTE, last_seen, NOW()) <= $thresholdMinutes
                ORDER BY role DESC, username ASC";
        $result = mysqli_query($this->db, $sql);
        $users = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
        }
        return $users;
    }
}