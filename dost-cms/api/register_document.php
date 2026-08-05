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

$registeredBy = $_SESSION['user_id'];

$type = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';
$direction = isset($_POST['direction']) ? ucfirst(strtolower(trim($_POST['direction']))) : 'Incoming';

$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if (!in_array($type, $validTypes) || $title === '' || $origin === '') {
    jsonError('Invalid or missing fields');
}

if (strlen($title) > 255 || strlen($origin) > 150) {
    jsonError('Fields are too long');
}

if (!in_array($direction, ['Incoming', 'Outgoing'])) {
    $direction = 'Incoming';
}

$reference = generateReferenceNumber($conn, $type);
if ($reference === null) {
    jsonError('Failed to generate a reference number. Please try again.', 500);
}

// Register the document and its initial update atomically so a failure can
// never leave a document without its registration record.
$conn->begin_transaction();

$stmt = $conn->prepare("INSERT INTO documents (reference_no, document_type, direction, title, origin_office, registered_by, status) VALUES (?, ?, ?, ?, ?, ?, 'Processing')");
if (!$stmt) {
    $conn->rollback();
    error_log('[register_document] prepare failed: ' . $conn->error);
    jsonError('Failed to register document. Please try again.', 500);
}
$stmt->bind_param('sssssi', $reference, $type, $direction, $title, $origin, $registeredBy);
if (!$stmt->execute()) {
    $conn->rollback();
    if ($stmt->errno === 1062) {
        jsonError('A document with this reference number already exists. Please try again.', 409);
    }
    error_log('[register_document] insert failed: ' . $stmt->error);
    jsonError('Failed to register document. Please try again.', 500);
}
$documentId = $stmt->insert_id;

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, updated_by, status, notes) VALUES (?, ?, 'Processing', 'Document registered')");
if (!$stmt) {
    $conn->rollback();
    error_log('[register_document] update prepare failed: ' . $conn->error);
    jsonError('Failed to register document. Please try again.', 500);
}
$stmt->bind_param('ii', $documentId, $registeredBy);
if (!$stmt->execute()) {
    $conn->rollback();
    error_log('[register_document] update insert failed: ' . $stmt->error);
    jsonError('Failed to register document. Please try again.', 500);
}

$conn->commit();

$typeLabel = $direction === 'Outgoing' ? 'New Outgoing Document' : 'New Incoming Document';
$message = "Reference No. " . $reference . " has been registered.\n" . $type . " • " . date('M d, Y');

// Notification is best-effort and runs after commit so a notification
// hiccup can never leave the document half-registered.
createNotification(
    $conn,
    $direction === 'Outgoing' ? 'outgoing' : 'incoming',
    $typeLabel,
    $message,
    $documentId,
    $reference
);

jsonSuccess(['reference' => $reference]);
