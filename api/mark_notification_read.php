<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/scope.php';

if (!csrf_verify()) {
    jsonError('Invalid CSRF token', 403);
}

$notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$markAll = isset($_POST['all']) ? (int)$_POST['all'] === 1 : false;

$scope = scope_where();

if ($markAll) {
    if (!empty($scope['params'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0 {$scope['where']}");
        if ($stmt) {
            $stmt->bind_param($scope['types'], ...$scope['params']);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    }
    jsonSuccess(['marked' => 'all']);
}

if ($notificationId <= 0) {
    jsonError('Missing notification id');
}

// Office scope: verify the notification belongs to the user's office.
$check = $conn->prepare("SELECT notification_id FROM notifications WHERE notification_id = ? {$scope['where']}");
if (!empty($scope['params'])) {
    $types = 'i' . $scope['types'];
    $params = array_merge([$notificationId], $scope['params']);
    $check->bind_param($types, ...$params);
} else {
    $check->bind_param('i', $notificationId);
}
$check->execute();
if ($check->get_result()->num_rows === 0) {
    jsonError('Notification not found', 404);
}
$check->close();

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
$stmt->bind_param('i', $notificationId);
$stmt->execute();

jsonSuccess();
