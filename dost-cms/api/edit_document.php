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
if ($role !== 'super_admin' && $role !== 'office_admin') {
    jsonError('Unauthorized', 403);
}

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$title     = isset($_POST['title'])     ? trim($_POST['title'])     : '';
$origin    = isset($_POST['origin'])    ? trim($_POST['origin'])    : '';
$type      = isset($_POST['type'])      ? strtoupper(trim($_POST['type'])) : '';
$direction = isset($_POST['direction']) ? ucfirst(strtolower(trim($_POST['direction']))) : '';

$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if ($reference === '' || $title === '' || $origin === '' || $type === '' || $direction === '') {
    jsonError('All fields are required');
}

if (!in_array($type, $validTypes)) {
    jsonError('Invalid document type');
}

if (!in_array($direction, ['Incoming', 'Outgoing'])) {
    jsonError('Invalid direction');
}

if (strlen($title) > 255) {
    jsonError('Title must be 255 characters or fewer');
}

if (strlen($origin) > 255) {
    jsonError('Originating office must be 255 characters or fewer');
}

$stmt = $conn->prepare("SELECT id FROM documents WHERE reference_no = ?");
if (!$stmt) {
    error_log('[edit_document] lookup prepare failed: ' . $conn->error);
    jsonError('Failed to update document. Please try again.', 500);
}
$stmt->bind_param('s', $reference);
if (!$stmt->execute()) {
    error_log('[edit_document] lookup failed: ' . $stmt->error);
    jsonError('Failed to update document. Please try again.', 500);
}
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    jsonError('Document not found');
}

$stmt = $conn->prepare("UPDATE documents SET title = ?, origin_office = ?, document_type = ?, direction = ? WHERE reference_no = ?");
if (!$stmt) {
    error_log('[edit_document] update prepare failed: ' . $conn->error);
    jsonError('Failed to update document. Please try again.', 500);
}
$stmt->bind_param('sssss', $title, $origin, $type, $direction, $reference);
if (!$stmt->execute()) {
    error_log('[edit_document] update failed: ' . $stmt->error);
    jsonError('Failed to update document. Please try again.', 500);
}

jsonSuccess();
