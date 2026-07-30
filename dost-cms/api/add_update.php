<?php
header('Content-Type: application/json');
session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$updatedBy = $_SESSION['user_id'];

$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($reference === '' || $notes === '') {
    jsonError('Missing fields');
}

$stmt = $conn->prepare("SELECT id, status FROM documents WHERE reference_no = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    jsonError('Document not found');
}
$stmt = $conn->prepare("
    INSERT INTO document_updates
    (document_id, updated_by, status, notes)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    'iiss',
    $row['id'],
    $updatedBy,
    $row['status'],
    $notes
);
$stmt->execute();

jsonSuccess();
