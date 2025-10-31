<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'CreateWeb — Jasa Pembuatan Website Profesional',
            'meta_description' => 'CreateWeb membantu bisnis Anda hadir online dengan website modern, cepat, dan responsif.',
            'brand' => 'CreateWeb',
        ];

        $this->view('home/index', $data);
    }
}