<?php
header('Content-Type: application/json');
session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$result = $conn->query("SELECT prefix, `separator`, padding, include_year FROM reference_settings WHERE id = 1");
$settings = $result->fetch_assoc();

echo json_encode($settings);
