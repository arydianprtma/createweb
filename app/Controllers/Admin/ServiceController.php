<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\ServicePrice;

class ServiceController extends Controller
{
    protected $servicePriceModel;

    public function __construct()
    {
        $this->servicePriceModel = new ServicePrice();
    }

    public function index()
    {
        $data = [
            'title' => 'Kelola Harga Layanan',
            'services' => $this->servicePriceModel->getAllPrices()
        ];

        return $this->view('admin/services/index', $data, 'layouts/admin');
    }

    // Tampilkan form tambah layanan baru
    public function new()
    {
        // Pastikan skema tabel memiliki kolom yang diperlukan
        if (method_exists($this->servicePriceModel, 'ensureSchema')) {
            $this->servicePriceModel->ensureSchema();
        }

        $data = [
            'title' => 'Tambah Layanan Baru',
            'errors' => [],
            'old' => [
                'service_code' => '',
                'service_name' => '',
                'description' => '',
                'features' => '',
                'price' => ''
            ]
        ];

        return $this->view('admin/services/new', $data, 'layouts/admin');
    }

    public function edit($serviceCode)
    {
        $service = $this->servicePriceModel->getPriceByServiceCode($serviceCode);
        if (!$service) {
            header('Location: /admin/services');
            exit;
        }
        
        $data = [
            'title' => 'Edit Harga Layanan',
            'service' => $service
        ];
        
        return $this->view('admin/services/edit', $data, 'layouts/admin');
        
        if (empty($service)) {
            header('Location: /admin/services');
            exit;
        }

        $data = [
            'title' => 'Edit Harga Layanan',
            'service' => $service
        ];

        return $this->view('admin/services/edit', $data);
    }

    public function update()
    {
        $serviceCode = $_POST['service_code'] ?? '';
        $price = $_POST['price'] ?? '';

        $this->servicePriceModel->updatePrice($serviceCode, $price);

        header('Location: /admin/services');
        exit;
    }

    // Proses membuat layanan baru
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/services/new');
            exit;
        }

        // Pastikan skema
        if (method_exists($this->servicePriceModel, 'ensureSchema')) {
            $this->servicePriceModel->ensureSchema();
        }

        $serviceCode = trim($_POST['service_code'] ?? '');
        $serviceName = trim($_POST['service_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        // Fitur: normalisasi teks dan konversi literal "\n" menjadi newline
        $featuresText = trim($_POST['features'] ?? '');
        // Ubah CRLF/CR ke LF untuk konsistensi
        $featuresText = str_replace(["\r\n", "\r"], "\n", $featuresText);
        // Konversi literal backslash-n menjadi newline asli
        // Misal pengguna mengetik "Item1\nItem2" di satu baris
        $featuresText = preg_replace('/\\\\n/', "\n", $featuresText);
        // Hindari karakter kontrol selain newline/tab
        $featuresText = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $featuresText);
        $price = trim($_POST['price'] ?? '');

        $errors = [];

        // Validasi kode layanan (slug)
        if ($serviceCode === '' || !preg_match('/^[a-z0-9_-]+$/', $serviceCode)) {
            $errors[] = 'Kode layanan wajib diisi (huruf kecil, angka, strip, atau underscore).';
        }
        if ($this->servicePriceModel->getPriceByServiceCode($serviceCode)) {
            $errors[] = 'Kode layanan sudah digunakan.';
        }

        // Validasi nama
        if ($serviceName === '' || mb_strlen($serviceName) < 3) {
            $errors[] = 'Nama layanan wajib diisi dan minimal 3 karakter.';
        }

        // Validasi harga
        $priceFloat = (float)$price;
        if ($price === '' || $priceFloat <= 0) {
            $errors[] = 'Harga wajib diisi dan harus lebih dari 0.';
        }

        // Fitur: konversi teks baris menjadi array JSON (sudah normalisasi newline)
        $featuresArray = [];
        if ($featuresText !== '') {
            $lines = preg_split('/\n/', $featuresText);
            foreach ($lines as $line) {
                // Sanitasi dasar: strip tags dan trim
                $item = trim(strip_tags($line));
                if ($item !== '') {
                    $featuresArray[] = $item;
                }
            }
        }

        // Logging untuk pelacakan sumber input fitur
        // Gunakan error_log agar tidak tergantung directory khusus
        $logPayload = [
            'time' => date('Y-m-d H:i:s'),
            'action' => 'admin_service_create_features_parse',
            'service_code' => $serviceCode,
            'features_raw' => $_POST['features'] ?? '',
            'features_normalized' => $featuresText,
            'features_array' => $featuresArray
        ];
        // Format sebagai JSON satu baris untuk mudah dibaca di log PHP
        @error_log('[CreateWeb] ' . json_encode($logPayload, JSON_UNESCAPED_UNICODE));

        if (!empty($errors)) {
            $data = [
                'title' => 'Tambah Layanan Baru',
                'errors' => $errors,
                'old' => [
                    'service_code' => $serviceCode,
                    'service_name' => $serviceName,
                    'description' => $description,
                    'features' => $featuresText,
                    'price' => $price
                ]
            ];
            return $this->view('admin/services/new', $data, 'layouts/admin');
        }

        // Simpan
        $this->servicePriceModel->createService(
            $serviceCode,
            $serviceName,
            $priceFloat,
            $description,
            $featuresArray
        );

        header('Location: /admin/services');
        exit;
    }
}