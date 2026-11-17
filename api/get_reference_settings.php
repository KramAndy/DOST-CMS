<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
secure_session_start();
require '../config/db.php';
require 'helpers.php';
require 'api_auth.php';

$result = $conn->query("SELECT `separator`, padding, include_year FROM reference_settings WHERE id = 1");
$settings = $result->fetch_assoc();

// The prefix comes from the user's assigned office.
$settings['prefix'] = office_preferred_prefix($conn, $_SESSION['office_id'] ?? null);

echo json_encode($settings);
