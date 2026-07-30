<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$updatedBy = $_SESSION['user_id'];

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$validStatuses = ['Received', 'Processing', 'Dispatched'];

if ($reference === '' || !in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid fields']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM documents WHERE reference_no = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Document not found']);
    exit;
}

$documentId = $row['id'];

$stmt = $conn->prepare("UPDATE documents SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $documentId);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, updated_by, status, notes) VALUES (?, ?, ?, 'Status updated')");
$stmt->bind_param('iis', $documentId, $updatedBy, $status);
$stmt->execute();

echo json_encode(['success' => true]);

