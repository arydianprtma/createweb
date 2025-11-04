<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\UserAdmin;

class AuthController extends Controller
{
    protected $userAdminModel;
    
    public function __construct()
    {
        $this->userAdminModel = new UserAdmin();
    }
    
    public function login()
    {
        // Jika sudah login, redirect ke dashboard
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: /admin/dashboard');
            exit;
        }

        $data = [
            'title' => 'Admin Login'
        ];

        return $this->view('admin/login', $data, 'layouts/admin_login');
    }

    public function auth()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Cek status akun terlebih dahulu
        $user = $this->userAdminModel->getUserByUsername($username);
        if ($user && isset($user['is_active']) && (int)$user['is_active'] === 0) {
            $data = [
                'title' => 'Admin Login',
                'error' => 'Akun dengan username "' . htmlspecialchars($username, ENT_QUOTES) . '" telah dinonaktifkan sementara waktu, silakan hubungi Superadmin atau Pengelola.'
            ];
            return $this->view('admin/login', $data, 'layouts/admin_login');
        }

        // Verifikasi dengan database
        if ($this->userAdminModel->verifyPassword($username, $password)) {
            // Set session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            // Simpan role untuk kontrol akses
            $_SESSION['admin_role'] = $user['role'] ?? 'Admin';
            
            header('Location: /admin/dashboard');
            exit;
        } else {
            $data = [
                'title' => 'Admin Login',
                'error' => 'Username atau password salah!'
            ];
            
            return $this->view('admin/login', $data, 'layouts/admin_login');
        }
    }

    public function logout()
    {
        // Hapus session admin
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_role']);
        
        // Redirect ke halaman login
        header('Location: /admin/login');
        exit;
    }
}