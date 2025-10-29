<?php

namespace App\Models;

class Message
{
    private $db;

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once _DIR_ . '/../config.php';
        
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

    public function getAllMessages()
    {
        $messages = [];
        
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return $messages;
        }
        
        $query = "SELECT * FROM messages ORDER BY created_at DESC";
        $result = mysqli_query($this->db, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
        }
        
        return $messages;
    }

    public function getRecentMessages($limit = 5)
    {
        $messages = [];
        if ($this->db === null) {
            return $messages;
        }
        $limit = max(1, (int)$limit);
        $query = "SELECT * FROM messages ORDER BY created_at DESC LIMIT $limit";
        $result = mysqli_query($this->db, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
        }
        return $messages;
    }

    public function getUnreadMessages()
    {
        $messages = [];
        
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return $messages;
        }
        
        $query = "SELECT * FROM messages WHERE is_read = 0 ORDER BY created_at DESC";
        $result = mysqli_query($this->db, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
        }
        
        return $messages;
    }

    public function getMessageById($id)
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return null;
        }
        
        $id = mysqli_real_escape_string($this->db, $id);
        $query = "SELECT * FROM messages WHERE id = '$id' LIMIT 1";
        $result = mysqli_query($this->db, $query);
        
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function createMessage($data)
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return false;
        }
        
        $name = mysqli_real_escape_string($this->db, $data['name']);
        $email = mysqli_real_escape_string($this->db, $data['email']);
        $subject = mysqli_real_escape_string($this->db, $data['subject']);
        $message = mysqli_real_escape_string($this->db, $data['message']);
        $now = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO messages (name, email, subject, message, created_at, updated_at) 
                  VALUES ('$name', '$email', '$subject', '$message', '$now', '$now')";
        
        if (mysqli_query($this->db, $query)) {
            $message_id = mysqli_insert_id($this->db);
            
            // Buat notifikasi untuk pesan baru
            $notification = new Notification();
            $notification->createNotification('new_message', 'Pesan baru dari ' . $name, $message_id);
            
            return $message_id;
        }
        
        return false;
    }

    public function markAsRead($id)
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return false;
        }
        
        $id = mysqli_real_escape_string($this->db, $id);
        $now = date('Y-m-d H:i:s');
        
        $query = "UPDATE messages SET is_read = 1, updated_at = '$now' WHERE id = '$id'";
        
        return mysqli_query($this->db, $query);
    }

    public function replyMessage($id, $reply)
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return false;
        }
        
        $id = mysqli_real_escape_string($this->db, $id);
        $reply = mysqli_real_escape_string($this->db, $reply);
        $now = date('Y-m-d H:i:s');
        
        $query = "UPDATE messages SET 
                is_replied = 1, 
                reply = '$reply', 
                reply_date = '$now', 
                updated_at = '$now' 
                WHERE id = '$id'";
        
        return mysqli_query($this->db, $query);
    }

    public function getMessageCount()
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return 0;
        }
        
        $query = "SELECT COUNT(*) as total FROM messages";
        $result = mysqli_query($this->db, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['total'];
        }
        
        return 0;
    }

    public function getUnreadMessageCount()
    {
        // Periksa apakah koneksi database tersedia
        if ($this->db === null) {
            return 0;
        }
        
        $query = "SELECT COUNT(*) as total FROM messages WHERE is_read = 0";
        $result = mysqli_query($this->db, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return $row['total'];
        }
        
        return 0;
    }
}