<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\ServicePrice;
use App\Models\Notification;

class OrderController extends Controller
{
    public function index(): void
    {
        // Ambil service_code dari query jika ada
        $serviceCode = $_GET['service_code'] ?? '';
        $service = null;
        $serviceModel = new ServicePrice();
        if (method_exists($serviceModel, 'ensureSchema')) {
            $serviceModel->ensureSchema();
        }
        $services = $serviceModel->getAllPrices();
        foreach ($services as $svc) {
            if (!empty($serviceCode) && isset($svc['service_code']) && $svc['service_code'] === $serviceCode) {
                $service = $svc;
                break;
            }
        }

        $success = $_SESSION['order_success'] ?? false;
        $error = $_SESSION['order_error'] ?? false;
        unset($_SESSION['order_success'], $_SESSION['order_error']);

        $data = [
            'title' => 'Pemesanan Layanan - CreateWeb',
            'meta_description' => 'Form pemesanan layanan CreateWeb',
            'brand' => 'CreateWeb',
            'service' => $service,
            'services' => $services,
            'success' => $success,
            'error' => $error,
        ];

        $this->view('order/index', $data);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /order');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $serviceCode = trim($_POST['service_code'] ?? '');
        $serviceName = trim($_POST['service_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        $errors = [];
        if ($name === '' || mb_strlen($name) < 3) {
            $errors[] = 'Nama wajib diisi dan minimal 3 karakter';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid';
        }
        if ($whatsapp === '' || !preg_match('/^\+?\d{10,15}$/', preg_replace('/\D+/', '', $whatsapp))) {
            $errors[] = 'Nomor WhatsApp tidak valid';
        }
        if ($serviceCode === '' || $serviceName === '') {
            $errors[] = 'Data layanan tidak lengkap';
        }

        if (!empty($errors)) {
            $_SESSION['order_error'] = implode('<br>', $errors);
            header('Location: /order?service_code=' . urlencode($serviceCode));
            exit;
        }

        $orderModel = new Order();
        // Pastikan skema tersedia sebelum membuat pesanan
        if (method_exists($orderModel, 'ensureSchema')) {
            $orderModel->ensureSchema();
        }
        $orderId = $orderModel->createOrder([
            'customer_name' => $name,
            'customer_email' => $email,
            'whatsapp' => $whatsapp,
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        if ($orderId) {
            // Ambil order untuk mendapatkan kode pesanan
            $createdOrder = method_exists($orderModel, 'getOrderById') ? $orderModel->getOrderById((int)$orderId) : null;
            $orderCode = is_array($createdOrder) ? ($createdOrder['order_code'] ?? '') : '';

            // Buat notifikasi dengan mencantumkan kode pesanan
            $notif = new Notification();
            $message = 'Pesanan baru dari ' . $name . ' untuk ' . $serviceName;
            if ($orderCode !== '') { $message .= ' (Kode: ' . $orderCode . ')'; }
            $notif->createNotification('new_order', $message, $orderId);

            // Pesan sukses untuk klien, sertakan kode jika tersedia
            $_SESSION['order_success'] = 'Pesanan berhasil dibuat' . ($orderCode !== '' ? ' dengan kode ' . $orderCode : '') . '. Kami akan menghubungi Anda via WhatsApp.';
        } else {
            $_SESSION['order_error'] = 'Gagal membuat pesanan. Silakan coba lagi.';
        }

        header('Location: /order?service_code=' . urlencode($serviceCode));
        exit;
    }
}