<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Models\ClientFeedback;

class FeedbackController
{
    public function show(string $token): void
    {
        $model = new ClientFeedback();
        $feedback = $model->getByToken($token);
        if (!$feedback) {
            http_response_code(404);
            View::render('feedback/notfound', [
                'pageTitle' => 'Tautan tidak ditemukan'
            ], 'layouts/main');
            return;
        }
        View::render('feedback/form', [
            'token' => $token,
            'feedback' => $feedback,
            'pageTitle' => 'Apa Kata Klien Kami'
        ], 'layouts/main');
    }

    public function submit(): void
    {
        $token = $_POST['token'] ?? '';
        // Fallback: jika token kosong, coba ambil dari referer /feedback/{token} atau dari query token
        if ($token === '' || !preg_match('/^[A-Za-z0-9]+$/', $token)) {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            $refPath = $ref ? parse_url($ref, PHP_URL_PATH) : '';
            if ($refPath && preg_match('/^\/feedback\/([A-Za-z0-9]+)/', (string)$refPath, $mm)) {
                $token = $mm[1];
            } elseif (!empty($_GET['token']) && preg_match('/^[A-Za-z0-9]+$/', (string)$_GET['token'])) {
                $token = (string)$_GET['token'];
            }
        }
        $name = trim($_POST['client_name'] ?? '') ?: null;
        $organization = trim($_POST['organization'] ?? '') ?: null;
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '') ?: null;

        $model = new ClientFeedback();
        $feedback = $model->getByToken($token);
        if (!$feedback) {
            http_response_code(404);
            View::render('feedback/notfound', [
                'pageTitle' => 'Tautan tidak ditemukan'
            ], 'layouts/main');
            return;
        }
        $ok = $model->submitFeedback($token, $name, $organization, $rating, $comment);
        if (!$ok) {
            http_response_code(400);
            echo 'Input rating tidak valid.';
            return;
        }

        View::render('feedback/success', [
            'pageTitle' => 'Terima kasih!',
        ], 'layouts/main');
    }
}