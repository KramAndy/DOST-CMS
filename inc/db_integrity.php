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

        // Office Management: create offices table if missing.
        $ot = $conn->query("SHOW TABLES LIKE 'offices'");
        if ($ot && $ot->num_rows === 0) {
            $conn->query(
                "CREATE TABLE offices (
                    office_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    office_code VARCHAR(20) NOT NULL,
                    office_name VARCHAR(100) NOT NULL,
                    preferred_prefix VARCHAR(50) NOT NULL DEFAULT '',
                    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                    UNIQUE KEY uk_office_code (office_code),
                    UNIQUE KEY uk_office_name (office_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        }

        // Office Management: add preferred_prefix column to offices if missing.
        $ppCol = $conn->query("SHOW COLUMNS FROM offices LIKE 'preferred_prefix'");
        if ($ppCol && $ppCol->num_rows === 0) {
            $conn->query(
                "ALTER TABLE offices ADD COLUMN preferred_prefix VARCHAR(50) NOT NULL DEFAULT '' AFTER office_name"
            );
        }

        // Office Management: add office_id column to users if missing.
        $oc = $conn->query("SHOW COLUMNS FROM users LIKE 'office_id'");
        if ($oc && $oc->num_rows === 0) {
            $conn->query(
                "ALTER TABLE users ADD COLUMN office_id INT(11) DEFAULT NULL AFTER version"
            );
            // Add foreign key constraint.
            $conn->query(
                "ALTER TABLE users ADD CONSTRAINT fk_users_office
                 FOREIGN KEY (office_id) REFERENCES offices(office_id)
                 ON DELETE SET NULL ON UPDATE CASCADE"
            );
        }

        // Office Management: seed default office and assign existing users.
        if (!empty($_SESSION['office_migrated'])) return;

        $officeCount = $conn->query("SELECT COUNT(*) AS c FROM offices");
        if ($officeCount && (int)$officeCount->fetch_assoc()['c'] === 0) {
            // Create a default office so existing data is never orphaned.
            $conn->query(
                "INSERT INTO offices (office_code, office_name, status)
                 VALUES ('DOST-R5', 'DOST Regional Office V', 'active')"
            );
        }

        // Assign any users without an office to the default active office.
        $defaultOffice = $conn->query(
            "SELECT office_id FROM offices WHERE status = 'active' ORDER BY office_id ASC LIMIT 1"
        );
        if ($defaultOffice && $defaultOffice->num_rows > 0) {
            $defaultId = (int)$defaultOffice->fetch_assoc()['office_id'];
            $conn->query(
                "UPDATE users SET office_id = $defaultId WHERE office_id IS NULL"
            );

            // Office-based scoping: add office_id to documents if missing.
            $docOc = $conn->query("SHOW COLUMNS FROM documents LIKE 'office_id'");
            if ($docOc && $docOc->num_rows === 0) {
                $conn->query(
                    "ALTER TABLE documents ADD COLUMN office_id INT(11) DEFAULT NULL AFTER registered_by"
                );
                $conn->query(
                    "ALTER TABLE documents ADD CONSTRAINT fk_documents_office
                     FOREIGN KEY (office_id) REFERENCES offices(office_id)
                     ON DELETE SET NULL ON UPDATE CASCADE"
                );
                // Assign existing documents to the default office.
                $conn->query(
                    "UPDATE documents SET office_id = $defaultId WHERE office_id IS NULL"
                );
            }

            // Office-based scoping: add office_id to notifications if missing.
            $notOc = $conn->query("SHOW COLUMNS FROM notifications LIKE 'office_id'");
            if ($notOc && $notOc->num_rows === 0) {
                $conn->query(
                    "ALTER TABLE notifications ADD COLUMN office_id INT(11) DEFAULT NULL AFTER priority"
                );
                $conn->query(
                    "ALTER TABLE notifications ADD CONSTRAINT fk_notifications_office
                     FOREIGN KEY (office_id) REFERENCES offices(office_id)
                     ON DELETE SET NULL ON UPDATE CASCADE"
                );
                // Assign existing notifications to the default office.
                $conn->query(
                    "UPDATE notifications SET office_id = $defaultId WHERE office_id IS NULL"
                );
            }
        }

        // Reference prefix migration: copy existing global prefix to the
        // default office's preferred_prefix if it's still empty.
        $prefixCheck = $conn->query("SELECT preferred_prefix FROM offices WHERE preferred_prefix != '' LIMIT 1");
        if (!$prefixCheck || $prefixCheck->num_rows === 0) {
            $globalPrefix = $conn->query("SELECT prefix FROM reference_settings WHERE id = 1");
            if ($globalPrefix && $globalPrefix->num_rows > 0) {
                $prefix = $globalPrefix->fetch_assoc()['prefix'];
                if ($prefix !== '') {
                    $conn->query("UPDATE offices SET preferred_prefix = '" . $conn->real_escape_string($prefix) . "' WHERE preferred_prefix = ''");
                }
            }
        }

        $_SESSION['office_migrated'] = 1;
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

    // Returns the preferred reference prefix configured for an office, or an
    // empty string when no office is assigned. The caller resolves the office
    // context (e.g. from the authenticated user) and passes the resolved
    // prefix into the generator, so generation never depends on session state.
    function office_preferred_prefix($conn, $office_id) {
        if (empty($office_id)) return '';
        $stmt = $conn->prepare("SELECT preferred_prefix FROM offices WHERE office_id = ?");
        if (!$stmt) return '';
        $officeId = (int)$office_id;
        $stmt->bind_param('i', $officeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return '';
        return (string)$row['preferred_prefix'];
    }

    // Loads the global reference formatting rules together with the derived
    // year components used to build a reference number.
    function referenceFormatSettings($conn) {
        $settingsResult = $conn->query("SELECT `separator`, padding, include_year FROM reference_settings WHERE id = 1");
        if (!$settingsResult || !($settings = $settingsResult->fetch_assoc())) return null;
        $includeYear = (bool)$settings['include_year'];
        $year = date('Y');
        return [
            'separator'    => $settings['separator'],
            'padding'      => (int)$settings['padding'],
            'include_year' => $includeYear,
            'year'         => $year,
            'seq_year'     => $includeYear ? $year : '',
        ];
    }

    // Formats a sequence number into a full reference number. Single source of
    // truth shared by live previews and actual reference generation.
    function formatReferenceNumber($prefix, $type, $seq, $sep, $padding, $includeYear, $year) {
        return $prefix . $sep . $type . $sep . ($includeYear ? $year . $sep : '') . str_pad($seq, $padding, '0', STR_PAD_LEFT);
    }

    // Atomically allocates the next sequence for a reference type and returns
    // the formatted reference number. Concurrent requests can never receive
    // the same number: the INSERT ... ON DUPLICATE KEY UPDATE serializes on
    // the counter row and returns a distinct LAST_INSERT_ID() per caller.
    // The INSERT path auto-seeds the counter from existing documents so
    // collisions with historical reference numbers are impossible.
    //
    // The office context (preferred prefix) is resolved by the caller and
    // passed in; this function never reads session or global state.
    function generateReferenceNumber($conn, $type, $prefix) {
        $format = referenceFormatSettings($conn);
        if ($format === null) return null;
        $sep = $format['separator'];
        $padding = $format['padding'];
        $includeYear = $format['include_year'];
        $year = $format['year'];
        $seqYear = $format['seq_year'];

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

        return formatReferenceNumber($prefix, $type, $seq, $sep, $padding, $includeYear, $year);
    }

    // Returns the reference number that would be allocated next WITHOUT
    // consuming a sequence. Used only for UI previews. Uses the exact same
    // formatting logic as generateReferenceNumber().
    function previewReferenceNumber($conn, $type, $prefix) {
        $format = referenceFormatSettings($conn);
        if ($format === null) return null;
        $sep = $format['separator'];
        $padding = $format['padding'];
        $includeYear = $format['include_year'];
        $year = $format['year'];
        $seqYear = $format['seq_year'];

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

        return formatReferenceNumber($prefix, $type, $nextSeq, $sep, $padding, $includeYear, $year);
    }
}
