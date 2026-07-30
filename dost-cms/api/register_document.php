<?php
header('Content-Type: application/json');
session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$registeredBy = $_SESSION['user_id'];

$type = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';

$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if (!in_array($type, $validTypes) || $title === '' || $origin === '') {
    jsonError('Invalid or missing fields');
}

$reference = generateReferenceNumber($conn, $type);

$stmt = $conn->prepare("INSERT INTO documents (reference_no, document_type, title, origin_office, registered_by, status) VALUES (?, ?, ?, ?, ?, 'Processing')");
$stmt->bind_param('ssssi', $reference, $type, $title, $origin, $registeredBy);
$stmt->execute();
$documentId = $stmt->insert_id;

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, status, notes) VALUES (?, 'Processing', 'Document registered')");
$stmt->bind_param('i', $documentId);
$stmt->execute();

jsonSuccess(['reference' => $reference]);
