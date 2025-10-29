<?php
require_once __DIR__ . '/../app/config.php';
\mysqli_report(MYSQLI_REPORT_OFF);
$db = @\mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
header('Content-Type: application/json');

if (!$db) {
    echo json_encode([ 'db' => false, 'orders_table' => false ]);
    exit;
}

$res = @\mysqli_query($db, "SHOW TABLES LIKE 'orders'");
$exists = $res && \mysqli_num_rows($res) > 0;

echo json_encode([ 'db' => true, 'orders_table' => $exists ]);