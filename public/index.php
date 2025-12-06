<?php

// Harden session cookie parameters BEFORE starting the session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Define APP_PATH, CONFIG_PATH, PUBLIC_PATH
define('APP_PATH', dirname(__DIR__) . '/app');
define('CONFIG_PATH', dirname(__DIR__) . '/config');
define('PUBLIC_PATH', dirname(__DIR__) . '/public');

// Dynamically determine BASE_URL
$baseUrl = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $script_name = $_SERVER['SCRIPT_NAME']; // e.g. /Quan_Ly_Chi_Tieu/public/index.php or /index.php
    $base_path = str_replace('/index.php', '', $script_name);
    $public_pos = strrpos($base_path, '/public');
    if ($public_pos !== false) {
        // XAMPP: /Quan_Ly_Chi_Tieu/public -> /Quan_Ly_Chi_Tieu/public (keep /public for assets)
        $base_path = substr($base_path, 0, $public_pos) . '/public';
    }
    if (!empty($base_path) && $base_path !== '/') {
        $baseUrl = $base_path;
    }
}
define('BASE_URL', $baseUrl); // e.g. /Quan_Ly_Chi_Tieu/public or empty for dev server


error_reporting(E_ALL); // Ensure error reporting is ON
ini_set('display_errors', 1); // Display errors on screen

// Set timezone to Vietnam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php'; 

// Load environment variables from .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Load constants
require_once CONFIG_PATH . '/constants.php';

// Removed manual require_once statements as Composer autoloader handles them

$app = new App\Core\App(); // Instantiate the App class
$app->run(); // Run the application