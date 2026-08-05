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
$status    = isset($_POST['status'])    ? trim($_POST['status'])    : '';
$notes     = isset($_POST['notes'])     ? trim($_POST['notes'])     : '';

$validStatuses = ['Received', 'Processing', 'Dispatched'];

if ($reference === '' || !in_array($status, $validStatuses)) {
    jsonError('Invalid fields');
}

if ($notes === '') {
    jsonError('Update notes are required');
}

if (strlen($notes) > 2000) {
    jsonError('Notes are too long');
}

$stmt = $conn->prepare("SELECT id, reference_no, document_type, status FROM documents WHERE reference_no = ?");
if (!$stmt) {
    error_log('[submit_update] lookup prepare failed: ' . $conn->error);
    jsonError('Failed to submit update. Please try again.', 500);
}
$stmt->bind_param('s', $reference);
if (!$stmt->execute()) {
    error_log('[submit_update] lookup failed: ' . $stmt->error);
    jsonError('Failed to submit update. Please try again.', 500);
}
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    jsonError('Document not found');
}

$documentId     = $row['id'];
$previousStatus = $row['status'];

$conn->begin_transaction();

$stmt = $conn->prepare("UPDATE documents SET status = ? WHERE id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log('[submit_update] status update prepare failed: ' . $conn->error);
    jsonError('Failed to submit update. Please try again.', 500);
}
$stmt->bind_param('si', $status, $documentId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[submit_update] status update failed: ' . $stmt->error);
    jsonError('Failed to submit update. Please try again.', 500);
}

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, updated_by, status, notes) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    $conn->rollback();
    error_log('[submit_update] insert prepare failed: ' . $conn->error);
    jsonError('Failed to submit update. Please try again.', 500);
}
$stmt->bind_param('iiss', $documentId, $updatedBy, $status, $notes);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[submit_update] insert failed: ' . $stmt->error);
    jsonError('Failed to submit update. Please try again.', 500);
}

$conn->commit();

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
