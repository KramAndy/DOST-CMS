<?php
// Concurrency-safe reference generation now lives in inc/db_integrity.php.
require_once __DIR__ . '/../inc/db_integrity.php';

function jsonSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
