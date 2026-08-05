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

$notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$markAll = isset($_POST['all']) ? (int)$_POST['all'] === 1 : false;

if ($markAll) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    jsonSuccess(['marked' => 'all']);
}

if ($notificationId <= 0) {
    jsonError('Missing notification id');
}

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
$stmt->bind_param('i', $notificationId);
$stmt->execute();

jsonSuccess();
