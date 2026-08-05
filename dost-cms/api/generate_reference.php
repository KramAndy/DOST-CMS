<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$type = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
$validTypes = ['IM', 'EL', 'SO', 'OO', 'MOA'];

if (!in_array($type, $validTypes)) {
    jsonError('Invalid document type');
}

$reference = generateReferenceNumber($conn, $type);

jsonSuccess(['reference' => $reference]);
