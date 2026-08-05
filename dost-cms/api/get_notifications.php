<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require '../inc/notifications.php';
require 'api_auth.php';

// Opportunistic overdue check so the dashboard reflects overdue state.
checkOverdueDocuments($conn);

$result = $conn->query(
    "SELECT
         notification_id,
         title,
         message,
         notification_type,
         reference_number,
         is_read,
         created_at
     FROM notifications
     ORDER BY is_read ASC, created_at DESC, notification_id DESC
     LIMIT 10"
);

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$countResult = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE is_read = 0");
$unreadCount = (int)$countResult->fetch_assoc()['cnt'];

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
