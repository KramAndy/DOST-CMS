<?php
// Database integrity safeguards for concurrent use.
// Ensures required tables and constraints exist, and provides a
// concurrency-safe reference number allocation + non-consuming preview.

if ((basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__))) {
    http_response_code(404);
    exit;
}

if (!function_exists('ensure_db_integrity')) {

    // Creates any missing supporting tables and constraints. Idempotent;
    // after the first run every check is a no-op, so per-request cost is nil.
    function ensure_db_integrity($conn) {
        static $done = false;
        if ($done) return;
        $done = true;

        // Atomic per-(type, year) sequence counters for reference numbers.
        $t = $conn->query("SHOW TABLES LIKE 'reference_counters'");
        if ($t && $t->num_rows === 0) {
            $conn->query(
                "CREATE TABLE reference_counters (
                    reference_type VARCHAR(10) NOT NULL,
                    seq_year VARCHAR(4) NOT NULL DEFAULT '',
                    last_sequence INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (reference_type, seq_year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        }

        // Prevent duplicate usernames at the database level. Skipped if
        // legacy duplicate rows exist so existing data is never broken.
        $idx = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'username'");
        if ($idx && $idx->num_rows === 0) {
            $dup = $conn->query("SELECT username FROM users GROUP BY username HAVING COUNT(*) > 1 LIMIT 1");
            if ($dup && $dup->num_rows === 0) {
                $conn->query("ALTER TABLE users ADD UNIQUE KEY username (username)");
            }
        }

        // Optimistic-locking version column for concurrent user edits.
        $vc = $conn->query("SHOW COLUMNS FROM users LIKE 'version'");
        if ($vc && $vc->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN version INT UNSIGNED NOT NULL DEFAULT 0 AFTER role");
        }
    }

    // Highest sequence already used by documents matching a type (and year).
    function maxSequenceFromDocuments($conn, $type, $seqYear, $sep) {
        if ($seqYear === '') {
            $stmt = $conn->prepare("SELECT reference_no FROM documents WHERE document_type = ?");
            $stmt->bind_param('s', $type);
        } else {
            $yearInt = (int)$seqYear;
            $stmt = $conn->prepare("SELECT reference_no FROM documents WHERE document_type = ? AND YEAR(created_at) = ?");
            $stmt->bind_param('si', $type, $yearInt);
        }
        if (!$stmt) return 0;
        $stmt->execute();
        $max = 0;
        while ($row = $stmt->get_result()->fetch_assoc()) {
            $parts = explode($sep, $row['reference_no']);
            $seq = (int)end($parts);
            if ($seq > $max) $max = $seq;
        }
        return $max;
    }

    // Atomically allocates the next sequence for a reference type and returns
    // the formatted reference number. Concurrent requests can never receive
    // the same number: the INSERT ... ON DUPLICATE KEY UPDATE serializes on
    // the counter row and returns a distinct LAST_INSERT_ID() per caller.
    // The INSERT path auto-seeds the counter from existing documents so
    // collisions with historical reference numbers are impossible.
    function generateReferenceNumber($conn, $type) {
        ensure_db_integrity($conn);

        $settingsResult = $conn->query("SELECT prefix, `separator`, padding, include_year FROM reference_settings WHERE id = 1");
        if (!$settingsResult || !($settings = $settingsResult->fetch_assoc())) return null;
        $prefix = $settings['prefix'];
        $sep = $settings['separator'];
        $padding = (int)$settings['padding'];
        $includeYear = (bool)$settings['include_year'];
        $year = date('Y');
        $seqYear = $includeYear ? $year : '';

        $stmt = $conn->prepare(
            "INSERT INTO reference_counters (reference_type, seq_year, last_sequence)
             SELECT ?, ?, LAST_INSERT_ID(
                 (SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(d.reference_no, ?, -1) AS UNSIGNED)), 0) + 1
                  FROM documents d
                  WHERE d.document_type = ? AND (? = '' OR YEAR(d.created_at) = ?))
             )
             ON DUPLICATE KEY UPDATE last_sequence = LAST_INSERT_ID(last_sequence + 1)"
        );
        if (!$stmt) return null;
        $stmt->bind_param('ssssss', $type, $seqYear, $sep, $type, $seqYear, $seqYear);
        if (!$stmt->execute()) return null;

        $seqResult = $conn->query("SELECT LAST_INSERT_ID() AS seq");
        $seq = $seqResult ? (int)$seqResult->fetch_assoc()['seq'] : 0;
        if ($seq <= 0) return null;

        return $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '') . str_pad($seq, $padding, '0', STR_PAD_LEFT);
    }

    // Returns the reference number that would be allocated next WITHOUT
    // consuming a sequence. Used only for UI previews.
    function previewReferenceNumber($conn, $type) {
        ensure_db_integrity($conn);

        $settingsResult = $conn->query("SELECT prefix, `separator`, padding, include_year FROM reference_settings WHERE id = 1");
        if (!$settingsResult || !($settings = $settingsResult->fetch_assoc())) return null;
        $prefix = $settings['prefix'];
        $sep = $settings['separator'];
        $padding = (int)$settings['padding'];
        $includeYear = (bool)$settings['include_year'];
        $year = date('Y');
        $seqYear = $includeYear ? $year : '';

        $nextSeq = 1;
        $stmt = $conn->prepare("SELECT last_sequence FROM reference_counters WHERE reference_type = ? AND seq_year = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $type, $seqYear);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $nextSeq = (int)$row['last_sequence'] + 1;
            }
        }
        if ($nextSeq === 1) {
            $nextSeq = maxSequenceFromDocuments($conn, $type, $seqYear, $sep) + 1;
        }

        return $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '') . str_pad($nextSeq, $padding, '0', STR_PAD_LEFT);
    }
}
