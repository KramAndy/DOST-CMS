<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/scope.php';

$scope = scope_where();

function stats_query($conn, $sql, $scope) {
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

$result = stats_query($conn, "SELECT COUNT(*) as total FROM documents WHERE 1=1 {$scope['where']}", $scope);
$total = $result ? (int)$result->fetch_assoc()['total'] : 0;

$result = stats_query($conn, "SELECT COUNT(*) as cnt FROM documents WHERE status = 'Received' {$scope['where']}", $scope);
$received = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

$result = stats_query($conn, "SELECT COUNT(*) as cnt FROM documents WHERE status = 'Processing' {$scope['where']}", $scope);
$processing = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

$result = stats_query($conn, "SELECT COUNT(*) as cnt FROM documents WHERE status = 'Dispatched' {$scope['where']}", $scope);
$dispatched = $result ? (int)$result->fetch_assoc()['cnt'] : 0;

echo json_encode([
    'total' => (int)$total,
    'received' => (int)$received,
    'processing' => (int)$processing,
    'dispatched' => (int)$dispatched
]);
