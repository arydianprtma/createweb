<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Settings;

class ContactController extends Controller
{
    private $settingsModel;
    
    public function __construct()
    {
        // Pastikan session dimulai
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->settingsModel = new Settings();
    }
    public function index(): void
    {
        // Ambil data dari database
        $settings = $this->settingsModel->getSettings('general');
        
        // Jika tidak ada di database, gunakan default
        if (!$settings) {
            $settings = [
                'site_name' => 'CreateWeb',
                'email' => 'info@createweb.com',
                'whatsapp_number' => '+62812-3456-7891',
                'address' => 'Jl. Contoh No. 123, Jakarta',
                'work_hours' => 'Senin - Jumat: 09:00 - 17:00',
                'instagram' => '@createweb_id',
                'tiktok' => '@createweb_id',
            ];
        }
        
        // Perbarui session
        $_SESSION['settings_general'] = $settings;
        
        // Ambil flash message dari session (untuk PRG)
        $success = $_SESSION['contact_success'] ?? false;
        $error = $_SESSION['contact_error'] ?? false;
        // Hapus setelah dibaca (flash)
        unset($_SESSION['contact_success'], $_SESSION['contact_error']);

        $data = [
            'title' => 'Kontak Kami - CreateWeb',
            'meta_description' => 'Hubungi tim CreateWeb untuk konsultasi website dan kebutuhan digital Anda',
            'brand' => 'CreateWeb',
            'success' => $success,
            'error' => $error,
            'settings' => $settings,
        ];

        $this->view('contact/index', $data);
    }

    public function send(): void
    {
        // Ambil data dari database
        $settings = $this->settingsModel->getSettings('general');
        
        // Jika tidak ada di database, gunakan default
        if (!$settings) {
            $settings = [
                'site_name' => 'CreateWeb',
                'email' => 'info@createweb.com',
                'whatsapp_number' => '+62 812-3456-7890',
                'address' => 'Jl. Contoh No. 123, Jakarta',
                'work_hours' => 'Senin - Jumat: 09:00 - 17:00',
                'instagram' => '@createweb_id',
                'tiktok' => '@createweb_id',
            ];
        }
        
        // Perbarui session
        $_SESSION['settings_general'] = $settings;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = filter_input(INPUT_POST, 'name');
            $name = $name !== null ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : '';
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $subject = filter_input(INPUT_POST, 'subject');
            $subject = $subject !== null ? htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') : '';
            $message = filter_input(INPUT_POST, 'message');
            $message = $message !== null ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';
            
            // Validasi input
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Nama harus diisi';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email tidak valid';
            }
            if (empty($subject)) {
                $errors[] = 'Subjek harus diisi';
            }
            if (empty($message)) {
                $errors[] = 'Pesan harus diisi';
            }
            
            if (empty($errors)) {
                $messageModel = new Message();
                $result = $messageModel->createMessage([
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => 'unread',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                if ($result) {
                    // Tambahkan notifikasi
                    $notificationModel = new Notification();
                    $notificationModel->createNotification('message', "Pesan baru dari $name");
                    // Set flash & redirect (PRG) untuk mencegah resubmit saat refresh
                    $_SESSION['contact_success'] = 'Pesan Anda telah terkirim. Kami akan menghubungi Anda segera.';
                    header('Location: /kontak');
                    exit;
                } else {
                    $_SESSION['contact_error'] = 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi nanti.';
                    header('Location: /kontak');
                    exit;
                }
            } else {
                // Validasi gagal: set flash error & redirect
                $_SESSION['contact_error'] = implode('<br>', $errors);
                header('Location: /kontak');
                exit;
            }
            
            // Tidak render langsung untuk mencegah resubmit; sudah redirect di atas
        } else {
            header('Location: /kontak');
            exit;
        }
    }
    
    private function sendWebSocketNotification(string $message): void
    {
        // Nonaktifkan sementara karena WebSocket server belum diinstal
        // Simpan notifikasi hanya ke database
        return;
    }
}