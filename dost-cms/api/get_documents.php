<?php
header('Content-Type: application/json');
session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$result = $conn->query("
    SELECT
        d.reference_no,
        d.document_type,
        d.title,
        d.origin_office,
        d.status,
        d.created_at,
        u.username AS registered_by
    FROM documents d
    LEFT JOIN users u ON d.registered_by = u.user_id
    ORDER BY d.id DESC
");
$documents = [];

while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

echo json_encode($documents);
