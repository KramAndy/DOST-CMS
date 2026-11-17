<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require '../inc/notifications.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/scope.php';

// Opportunistic maintenance so the dashboard reflects overdue state and
// expired notifications are removed without any manual trigger.
checkOverdueDocuments($conn);
cleanupOldNotifications($conn);

$scope = scope_where();

function notifications_query($conn, $sql, $scope) {
    if (!empty($scope['params'])) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($scope['types'], ...$scope['params']);
            $stmt->execute();
            return $stmt->get_result();
        }
        return false;
    }
    return $conn->query($sql);
}

$result = notifications_query($conn,
    "SELECT
         notification_id,
         title,
         message,
         notification_type,
         reference_number,
         is_read,
         created_at
     FROM notifications
     WHERE 1=1 {$scope['where']}
     ORDER BY is_read ASC, created_at DESC, notification_id DESC
     LIMIT 10",
    $scope
);

$notifications = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

$countResult = notifications_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE is_read = 0 {$scope['where']}", $scope);
$unreadCount = $countResult ? (int)$countResult->fetch_assoc()['cnt'] : 0;

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
