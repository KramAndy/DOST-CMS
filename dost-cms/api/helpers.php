<?php
function jsonSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function generateReferenceNumber($conn, $type) {
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

    return $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '') . str_pad($nextSeq, $padding, '0', STR_PAD_LEFT);
}
