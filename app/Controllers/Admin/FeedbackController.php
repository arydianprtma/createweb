<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\View;
use App\Models\ClientFeedback;

class FeedbackController
{
    public function index(): void
    {
        $model = new ClientFeedback();
        $items = $model->all();
        View::render('admin/feedback/index', [
            'items' => $items,
            'pageTitle' => 'Apa Kata Klien Kami'
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $model = new ClientFeedback();
        $created = $model->createShareLink($orderId);
        $shareUrl = '/feedback/' . $created['token'];
        $items = $model->all();
        View::render('admin/feedback/index', [
            'items' => $items,
            'created' => $created,
            'shareUrl' => $shareUrl,
            'pageTitle' => 'Apa Kata Klien Kami'
        ], 'layouts/admin');
    }
}