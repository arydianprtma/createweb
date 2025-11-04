<?php

namespace App\Models;

class ServicePrice
{
    private $db;

    public function __construct()
    {
        // Memuat konfigurasi database
        require_once _DIR_ . '/../config.php';
        $this->db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    public function getAllPrices()
    {
        $query = "SELECT * FROM service_prices";
        $result = mysqli_query($this->db, $query);
        $prices = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $prices[] = $row;
        }
        
        return $prices;
    }

    public function getPriceByServiceCode($serviceCode)
    {
        $serviceCode = mysqli_real_escape_string($this->db, $serviceCode);
        $query = "SELECT * FROM service_prices WHERE service_code = '$serviceCode' LIMIT 1";
        $result = mysqli_query($this->db, $query);
        
        return mysqli_fetch_assoc($result);
    }

    public function updatePrice($serviceCode, $price)
    {
        $serviceCode = mysqli_real_escape_string($this->db, $serviceCode);
        $price = (float) $price;
        $updated_at = date('Y-m-d H:i:s');
        
        $query = "UPDATE service_prices SET price = $price, updated_at = '$updated_at' 
                  WHERE service_code = '$serviceCode'";
                  
        return mysqli_query($this->db, $query);
    }

    // Untuk migrasi database
    public function createServicePricesTable()
    {
        // Menggunakan mysqli langsung untuk membuat tabel
        $query = "CREATE TABLE IF NOT EXISTS service_prices (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            service_code VARCHAR(50) NOT NULL,
            service_name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT NULL,
            features TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY service_code (service_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        mysqli_query($this->db, $query);

        // Kode array field dan forge sudah tidak digunakan lagi karena menggunakan query langsung

        // Insert default data
        $this->insertDefaultPrices();
    }

    // Memastikan kolom tambahan tersedia (untuk tabel yang sudah ada)
    public function ensureSchema()
    {
        $columns = ['description', 'features', 'is_active'];
        foreach ($columns as $col) {
            $check = mysqli_query($this->db, "SHOW COLUMNS FROM service_prices LIKE '$col'");
            if ($check && mysqli_num_rows($check) === 0) {
                if ($col === 'description') {
                    mysqli_query($this->db, "ALTER TABLE service_prices ADD COLUMN description TEXT NULL");
                } elseif ($col === 'features') {
                    mysqli_query($this->db, "ALTER TABLE service_prices ADD COLUMN features TEXT NULL");
                } elseif ($col === 'is_active') {
                    mysqli_query($this->db, "ALTER TABLE service_prices ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
                }
            }
        }
    }

    private function insertDefaultPrices()
    {
        $timestamp = date('Y-m-d H:i:s');
        
        $services = [
            ['company_profile', 'Company Profile', 3500000, 'Website profil perusahaan untuk memperkuat kepercayaan dan reputasi bisnis Anda.',
                ['Desain profesional','Halaman tentang perusahaan','Portofolio proyek','Formulir kontak']],
            ['ecommerce', 'E-Commerce', 7500000, 'Toko online lengkap dengan katalog produk dan sistem checkout yang aman.',
                ['Katalog produk','Keranjang belanja','Pembayaran online','Manajemen pesanan']],
            ['landing_page', 'Landing Page', 2500000, 'Halaman penawaran khusus untuk kampanye pemasaran yang efektif.',
                ['Desain konversi tinggi','Call-to-action efektif','Formulir lead capture','Analitik performa']],
            ['custom_development', 'Custom Development', 10000000, 'Solusi kustom sesuai kebutuhan unik bisnis Anda.',
                ['Aplikasi web kustom','Sistem manajemen konten','Integrasi API','Solusi bisnis spesifik']],
            ['optimization', 'Optimasi & Maintenance', 1500000, 'Perawatan, peningkatan performa, dan keamanan berkala.',
                ['Optimasi kecepatan','Update keamanan','Backup reguler','Monitoring 24/7']],
            ['integration', 'Integrasi Sistem', 5000000, 'Integrasi API, CRM, pembayaran, dan lainnya.',
                ['Integrasi payment gateway','Integrasi CRM','Integrasi media sosial','Integrasi API pihak ketiga']]
        ];
        
        foreach ($services as $service) {
            $service_code = mysqli_real_escape_string($this->db, $service[0]);
            $service_name = mysqli_real_escape_string($this->db, $service[1]);
            $price = (float) $service[2];
            $description = mysqli_real_escape_string($this->db, $service[3]);
            $featuresJson = mysqli_real_escape_string($this->db, json_encode($service[4], JSON_UNESCAPED_UNICODE));
            
            // Cek apakah data sudah ada
            $check_query = "SELECT id FROM service_prices WHERE service_code = '$service_code' LIMIT 1";
            $result = mysqli_query($this->db, $check_query);
            
            if (mysqli_num_rows($result) == 0) {
                $query = "INSERT INTO service_prices (service_code, service_name, price, description, features, is_active, created_at, updated_at) 
                          VALUES ('$service_code', '$service_name', $price, '$description', '$featuresJson', 1, '$timestamp', '$timestamp')";
                mysqli_query($this->db, $query);
            }
        }
    }

    // Membuat layanan baru lengkap dengan deskripsi dan fitur
    public function createService($serviceCode, $serviceName, $price, $description = '', $featuresArray = [], $isActive = 1)
    {
        $serviceCode = mysqli_real_escape_string($this->db, $serviceCode);
        $serviceName = mysqli_real_escape_string($this->db, $serviceName);
        $price = (float)$price;
        $description = mysqli_real_escape_string($this->db, $description);
        $featuresJson = mysqli_real_escape_string($this->db, json_encode($featuresArray, JSON_UNESCAPED_UNICODE));
        $isActive = (int)$isActive;
        $timestamp = date('Y-m-d H:i:s');

        $query = "INSERT INTO service_prices (service_code, service_name, price, description, features, is_active, created_at, updated_at)
                  VALUES ('$serviceCode', '$serviceName', $price, '$description', '$featuresJson', $isActive, '$timestamp', '$timestamp')";
        return mysqli_query($this->db, $query);
    }
}