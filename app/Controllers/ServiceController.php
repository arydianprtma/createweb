<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ServicePrice;

class ServiceController extends Controller
{
    public function index(): void
    {
        $serviceModel = new ServicePrice();
        // Pastikan kolom tambahan tersedia jika diperlukan
        if (method_exists($serviceModel, 'ensureSchema')) {
            $serviceModel->ensureSchema();
        }

        $data = [
            'title' => 'Layanan Kami - CreateWeb',
            'meta_description' => 'Layanan pembuatan website profesional dari CreateWeb',
            'brand' => 'CreateWeb',
            'services' => $serviceModel->getAllPrices()
        ];

        $this->view('/service/index', $data);
    }
}