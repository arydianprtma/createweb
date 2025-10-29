<?php
// File: index.php (root directory)
// Redirect semua request ke public/index.php

// Aktifkan tampilan error untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definisikan konstanta untuk base path
define('BASE_PATH', __DIR__);

// Include file public/index.php
require_once __DIR__ . '/public/index.php';