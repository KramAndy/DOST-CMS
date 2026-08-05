<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require '../inc/notifications.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/csrf.php';

if (!csrf_verify()) {
    jsonError('Invalid CSRF token', 403);
}

$updatedBy = $_SESSION['user_id'];

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$validStatuses = ['Received', 'Processing', 'Dispatched'];

if ($reference === '' || !in_array($status, $validStatuses)) {
    jsonError('Invalid fields');
}

$stmt = $conn->prepare("SELECT id, reference_no, document_type, status FROM documents WHERE reference_no = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    jsonError('Document not found');
}

$documentId = $row['id'];
$previousStatus = $row['status'];

$stmt = $conn->prepare("UPDATE documents SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $documentId);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, updated_by, status, notes) VALUES (?, ?, ?, 'Status updated')");
$stmt->bind_param('iis', $documentId, $updatedBy, $status);
$stmt->execute();

if ($status === 'Dispatched') {
    createNotification(
        $conn,
        'dispatched',
        'Document Dispatched',
        "Reference No. " . $reference . " has been dispatched.",
        $documentId,
        $reference
    );
} elseif ($status === 'Processing' && $previousStatus === 'Received') {
    createNotification(
        $conn,
        'processing',
        'Document Processing',
        "Reference No. " . $reference . " is now being processed.",
        $documentId,
        $reference
    );
}

jsonSuccess();
