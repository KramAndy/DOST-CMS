<?php
header('Content-Type: application/json');
session_start();	
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$prefix = isset($_POST['prefix']) ? trim($_POST['prefix']) : '';
$separator = isset($_POST['separator']) ? trim($_POST['separator']) : '';
$padding = isset($_POST['padding']) ? (int)$_POST['padding'] : 4;
$includeYear = isset($_POST['include_year']) ? (int)$_POST['include_year'] : 1;

if ($prefix === '' || $separator === '' || $padding < 1) {
    jsonError('Invalid fields');
}

$stmt = $conn->prepare("UPDATE reference_settings SET prefix = ?, `separator` = ?, padding = ?, include_year = ? WHERE id = 1");
$stmt->bind_param('ssii', $prefix, $separator, $padding, $includeYear);
$stmt->execute();

jsonSuccess();
