<?php
// Cron/scheduled-task friendly endpoint for the overdue check.
// Example: php api/run_overdue_check.php  (or hit via HTTP with a session)
// Returns JSON with the number of overdue notifications created.
// CLI execution is allowed without a session; HTTP requests must be
// authenticated and carry a valid CSRF token.
header('Content-Type: application/json');

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    require_once __DIR__ . '/../inc/session.php';
    secure_session_start();
    require 'api_auth.php';
    require_once __DIR__ . '/../inc/csrf.php';
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

require __DIR__ . '/../config/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/../inc/notifications.php';

$created = checkOverdueDocuments($conn);

echo json_encode([
    'success' => true,
    'overdue_created' => $created
]);
