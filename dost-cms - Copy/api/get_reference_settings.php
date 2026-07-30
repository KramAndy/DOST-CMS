<?php
header('Content-Type: application/json');
session_start();
include('../config/db.php');

$result = $conn->query("SELECT prefix, `separator`, padding, include_year FROM reference_settings WHERE id = 1");
$settings = $result->fetch_assoc();

echo json_encode($settings);
