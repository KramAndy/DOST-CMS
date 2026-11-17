<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/scope.php';

$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if ($reference === '') {
    jsonError('Missing reference');
}

$scope = scope_where('d');

$stmt = $conn->prepare("
    SELECT
        du.status,
        du.notes,
        du.created_at,
        u.username AS updated_by
    FROM document_updates du
    JOIN documents d
        ON du.document_id = d.id
    LEFT JOIN users u
        ON du.updated_by = u.user_id
    WHERE d.reference_no = ? {$scope['where']}
    ORDER BY du.created_at DESC
");
if (!empty($scope['params'])) {
    $types = 's' . $scope['types'];
    $params = array_merge([$reference], $scope['params']);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('s', $reference);
}
$stmt->execute();
$result = $stmt->get_result();

$updates = [];

while ($row = $result->fetch_assoc()) {
    $updates[] = $row;
}

echo json_encode($updates);
