<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if (!in_array($type, $validTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid document type']);
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

echo json_encode(['success' => true, 'reference' => $reference]);
