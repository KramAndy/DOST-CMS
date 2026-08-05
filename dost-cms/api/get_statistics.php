<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$result = $conn->query("SELECT COUNT(*) as total FROM documents");
$total = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE status = 'Received'");
$received = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE status = 'Processing'");
$processing = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM documents WHERE status = 'Dispatched'");
$dispatched = $result->fetch_assoc()['cnt'];

echo json_encode([
    'total' => (int)$total,
    'received' => (int)$received,
    'processing' => (int)$processing,
    'dispatched' => (int)$dispatched
]);
