<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require_once __DIR__ . '/../inc/rbac.php';
require_once __DIR__ . '/../inc/csrf.php';

if (!csrf_verify()) {
    jsonError('Invalid CSRF token', 403);
}

$role = rbac_current_role();
if ($role !== 'super_admin') {
    jsonError('Unauthorized', 403);
}

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';

if ($reference === '') {
    jsonError('Reference number is required');
}

$stmt = $conn->prepare("SELECT id FROM documents WHERE reference_no = ?");
if (!$stmt) {
    error_log('[delete_document] lookup prepare failed: ' . $conn->error);
    jsonError('Failed to delete document. Please try again.', 500);
}
$stmt->bind_param('s', $reference);
if (!$stmt->execute()) {
    error_log('[delete_document] lookup failed: ' . $stmt->error);
    jsonError('Failed to delete document. Please try again.', 500);
}
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    jsonError('Document not found');
}

$documentId = $row['id'];

$conn->begin_transaction();

$stmt = $conn->prepare("DELETE FROM document_updates WHERE document_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log('[delete_document] delete updates prepare failed: ' . $conn->error);
    jsonError('Failed to delete document. Please try again.', 500);
}
$stmt->bind_param('i', $documentId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[delete_document] delete updates failed: ' . $stmt->error);
    jsonError('Failed to delete document. Please try again.', 500);
}

$stmt = $conn->prepare("DELETE FROM notifications WHERE related_document_id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log('[delete_document] delete notifications prepare failed: ' . $conn->error);
    jsonError('Failed to delete document. Please try again.', 500);
}
$stmt->bind_param('i', $documentId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[delete_document] delete notifications failed: ' . $stmt->error);
    jsonError('Failed to delete document. Please try again.', 500);
}

$stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
if (!$stmt) {
    $conn->rollback();
    error_log('[delete_document] delete doc prepare failed: ' . $conn->error);
    jsonError('Failed to delete document. Please try again.', 500);
}
$stmt->bind_param('i', $documentId);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[delete_document] delete doc failed: ' . $stmt->error);
    jsonError('Failed to delete document. Please try again.', 500);
}

$conn->commit();

jsonSuccess();
