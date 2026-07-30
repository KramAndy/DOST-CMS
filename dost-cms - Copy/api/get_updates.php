<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';

if ($reference === '') {
    echo json_encode(['success' => false, 'message' => 'Missing reference']);
    exit;
}

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
    WHERE d.reference_no = ?
    ORDER BY du.created_at DESC
");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();

$updates = [];

while ($row = $result->fetch_assoc()) {
    $updates[] = $row;
}

echo json_encode($updates);
