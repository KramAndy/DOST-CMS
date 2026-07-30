<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$updatedBy = $_SESSION['user_id'];

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($reference === '' || $notes === '') {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$stmt = $conn->prepare("SELECT id, status FROM documents WHERE reference_no = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Document not found']);
    exit;
}
$stmt = $conn->prepare("
    INSERT INTO document_updates
    (document_id, updated_by, status, notes)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    'iiss',
    $row['id'],
    $updatedBy,
    $row['status'],
    $notes
);
$stmt->execute();

echo json_encode(['success' => true]);
