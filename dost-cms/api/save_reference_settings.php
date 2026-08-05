<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/csrf.php';

if (!csrf_verify()) {
    jsonError('Invalid CSRF token', 403);
}

$prefix = isset($_POST['prefix']) ? trim($_POST['prefix']) : '';
$separator = isset($_POST['separator']) ? trim($_POST['separator']) : '';
$padding = isset($_POST['padding']) ? (int)$_POST['padding'] : 4;
$includeYear = isset($_POST['include_year']) ? (int)$_POST['include_year'] : 1;

if ($prefix === '' || $separator === '' || $padding < 1 || $padding > 12) {
    jsonError('Invalid fields');
}

$includeYear = $includeYear === 1 ? 1 : 0;

$stmt = $conn->prepare("UPDATE reference_settings SET prefix = ?, `separator` = ?, padding = ?, include_year = ? WHERE id = 1");
$stmt->bind_param('ssii', $prefix, $separator, $padding, $includeYear);
$stmt->execute();

jsonSuccess();
