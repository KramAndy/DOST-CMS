<?php
// Block direct HTTP access to this file (it must only be included).
if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

// Production hardening: never render PHP errors to the browser.
// Set APP_DEBUG=1 (dev) to re-enable inline error display.
if (getenv('APP_DEBUG') !== '1') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'dost_cms';

$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log('[dost-cms] DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Service temporarily unavailable. Please try again later.');
}

$conn->set_charset("utf8mb4");
