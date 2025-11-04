<?php
declare(strict_types=1);
namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $viewsDir = _DIR_ . '/../Views/';
        $viewFile = $viewsDir . $view . '.php';
        $layoutFile = $viewsDir . $layout . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo 'View file not found: ' . htmlspecialchars($viewFile);
            return;
        }

        // Closure untuk isi konten utama
        $content = function () use ($viewFile, $data) {
            extract($data, EXTR_SKIP);
            include $viewFile;
        };

        extract($data, EXTR_SKIP);

        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            $content();
        }
    }
}