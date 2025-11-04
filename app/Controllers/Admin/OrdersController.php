<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Order;

class OrdersController extends Controller
{
    public function __construct()
    {
        // Pastikan admin sudah login
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function index()
    {
        $orderModel = new Order();
        if (method_exists($orderModel, 'ensureSchema')) {
            $orderModel->ensureSchema();
        }
        
        // Ambil filter dari query parameters
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['payment_status'])) {
            $filters['payment_status'] = $_GET['payment_status'];
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $orders = method_exists($orderModel, 'getAllOrders') ? 
                  $orderModel->getAllOrders($filters) : 
                  $orderModel->getRecentOrders(50);

        $data = [
            'title' => 'Pesanan Client',
            'orders' => $orders,
            'filters' => $filters,
        ];

        return $this->view('admin/orders/index', $data, 'layouts/admin');
    }

    public function show(int $id)
    {
        $orderModel = new Order();
        if (method_exists($orderModel, 'ensureSchema')) {
            $orderModel->ensureSchema();
        }
        $order = method_exists($orderModel, 'getOrderById') ? $orderModel->getOrderById($id) : null;
        if (!$order) {
            $_SESSION['error'] = 'Pesanan tidak ditemukan';
            header('Location: /admin/orders');
            exit;
        }

        $data = [
            'title' => 'Detail Pesanan',
            'order' => $order,
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['success'], $_SESSION['error']);

        return $this->view('admin/orders/view', $data, 'layouts/admin');
    }

    public function updateStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/orders');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = [
            // status baru
            'draft',
            'waiting_payment',
            'design_phase',
            'revision',
            'development',
            'testing',
            'deployment',
            'completed',
            'maintenance',
            'cancelled',
            // fallback lama
            'pending',
            'process',
        ];
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $_SESSION['error'] = 'Data tidak valid';
            header('Location: /admin/orders');
            exit;
        }

        $orderModel = new Order();
        if (method_exists($orderModel, 'updateStatus') && $orderModel->updateStatus($id, $status)) {
            $_SESSION['success'] = 'Status pesanan berhasil diperbarui';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui status pesanan';
        }

        header('Location: /admin/orders/view/' . $id);
        exit;
    }

    public function updatePaymentStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/orders');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $allowed = ['pending', 'dp', 'paid', 'cancelled'];
        if ($id <= 0 || !in_array($paymentStatus, $allowed, true)) {
            $_SESSION['error'] = 'Data tidak valid';
            header('Location: /admin/orders');
            exit;
        }

        $orderModel = new Order();
        if (method_exists($orderModel, 'updatePaymentStatus') && $orderModel->updatePaymentStatus($id, $paymentStatus)) {
            $_SESSION['success'] = 'Status pembayaran berhasil diperbarui';
        } else {
            $_SESSION['error'] = 'Gagal memperbarui status pembayaran';
        }

        header('Location: /admin/orders/view/' . $id);
        exit;
    }
}