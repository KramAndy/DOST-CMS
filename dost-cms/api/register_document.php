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

$stmt = $conn->prepare("INSERT INTO documents (reference_no, document_type, direction, title, origin_office, registered_by, status) VALUES (?, ?, ?, ?, ?, ?, 'Processing')");
$stmt->bind_param('sssssi', $reference, $type, $direction, $title, $origin, $registeredBy);
$stmt->execute();
$documentId = $stmt->insert_id;

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, updated_by, status, notes) VALUES (?, ?, 'Processing', 'Document registered')");
$stmt->bind_param('ii', $documentId, $registeredBy);
$stmt->execute();

$typeLabel = $direction === 'Outgoing' ? 'New Outgoing Document' : 'New Incoming Document';
$message = "Reference No. " . $reference . " has been registered.\n" . $type . " • " . date('M d, Y');

createNotification(
    $conn,
    $direction === 'Outgoing' ? 'outgoing' : 'incoming',
    $typeLabel,
    $message,
    $documentId,
    $reference
);

jsonSuccess(['reference' => $reference]);
