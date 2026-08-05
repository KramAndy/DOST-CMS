<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';
require_once __DIR__ . '/../inc/csrf.php';

if (!csrf_verify()) {
    jsonError('Invalid CSRF token', 403);
}

$updatedBy = $_SESSION['user_id'];

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($reference === '' || $notes === '') {
    jsonError('Missing fields');
}

if (strlen($notes) > 2000) {
    jsonError('Notes are too long');
}

// Look up and record the update atomically so a concurrently deleted
// document cannot produce a dangling update record.
$conn->begin_transaction();

$stmt = $conn->prepare("SELECT id, status FROM documents WHERE reference_no = ?");
if (!$stmt) {
    $conn->rollback();
    error_log('[add_update] lookup prepare failed: ' . $conn->error);
    jsonError('Failed to add update. Please try again.', 500);
}
$stmt->bind_param('s', $reference);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[add_update] lookup failed: ' . $stmt->error);
    jsonError('Failed to add update. Please try again.', 500);
}
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    $conn->rollback();
    jsonError('Document not found');
}

$stmt = $conn->prepare("
    INSERT INTO document_updates
    (document_id, updated_by, status, notes)
    VALUES (?, ?, ?, ?)
");
if (!$stmt) {
    $conn->rollback();
    error_log('[add_update] insert prepare failed: ' . $conn->error);
    jsonError('Failed to add update. Please try again.', 500);
}
$stmt->bind_param(
    'iiss',
    $row['id'],
    $updatedBy,
    $row['status'],
    $notes
);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[add_update] insert failed: ' . $stmt->error);
    jsonError('Failed to add update. Please try again.', 500);
}

$conn->commit();

jsonSuccess();
