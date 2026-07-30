<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$registeredBy = $_SESSION['user_id'];

$type = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';


$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if (!in_array($type, $validTypes) || $title === '' || $origin === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing fields']);
    exit;
}

$settingsResult = $conn->query("SELECT prefix, `separator`, padding, include_year FROM reference_settings WHERE id = 1");
$settings = $settingsResult->fetch_assoc();
$prefix = $settings['prefix'];
$sep = $settings['separator'];
$padding = (int)$settings['padding'];
$includeYear = (bool)$settings['include_year'];
$year = date('Y');

$likePrefix = $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '');

$stmt = $conn->prepare("SELECT reference_no FROM documents WHERE reference_no LIKE CONCAT(?, '%') ORDER BY id DESC LIMIT 1");
$stmt->bind_param('s', $likePrefix);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $parts = explode($sep, $row['reference_no']);
    $lastSeq = (int)end($parts);
    $nextSeq = $lastSeq + 1;
} else {
    $nextSeq = 1;
}

$reference = $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '') . str_pad($nextSeq, $padding, '0', STR_PAD_LEFT);

$stmt = $conn->prepare("INSERT INTO documents (reference_no, document_type, title, origin_office, registered_by, status) VALUES (?, ?, ?, ?, ?, 'Processing')");
$stmt->bind_param('ssssi', $reference, $type, $title, $origin, $registeredBy );
$stmt->execute();
$documentId = $stmt->insert_id;

$stmt = $conn->prepare("INSERT INTO document_updates (document_id, status, notes) VALUES (?, 'Processing', 'Document registered')");
$stmt->bind_param('i', $documentId);
$stmt->execute();

echo json_encode(['success' => true, 'reference' => $reference]);
