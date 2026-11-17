<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/scope.php';

$scope = scope_where('d');

$query = "
    SELECT
        d.reference_no,
        d.document_type,
        d.title,
        d.origin_office,
        d.direction,
        d.status,
        d.created_at,
        u.username AS registered_by
    FROM documents d
    LEFT JOIN users u ON d.registered_by = u.user_id
    WHERE 1=1 {$scope['where']}
    ORDER BY d.id DESC
";

if (!empty($scope['params'])) {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($scope['types'], ...$scope['params']);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = false;
    }
} else {
    $result = $conn->query($query);
}
$documents = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
}

echo json_encode($documents);
