<?php
// Centralized notification service.
// All notification creation and overdue detection lives here so new
// notification types can be added without touching the API endpoints.

if (!function_exists('createNotification')) {

    // Inserts a notification. Uses INSERT IGNORE with a unique key on
    // (notification_type, related_document_id) so duplicates are never created.
    function createNotification($conn, $type, $title, $message, $documentId, $referenceNumber, $priority = 'normal') {
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO notifications
                (title, message, notification_type, related_document_id, reference_number, priority)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        $stmt->bind_param('sssiss', $title, $message, $type, $documentId, $referenceNumber, $priority);
        $stmt->execute();
        $created = $stmt->affected_rows > 0;
        $stmt->close();
        return $created;
    }

    // Permanently removes notifications older than 10 days. Runs as a single
    // DELETE so cleanup stays lightweight; failures are silent and never
    // interrupt notification loading.
    function cleanupOldNotifications($conn) {
        $stmt = $conn->prepare(
            "DELETE FROM notifications
             WHERE created_at < (NOW() - INTERVAL 10 DAY)"
        );
        if (!$stmt) return 0;
        $stmt->execute();
        $deleted = (int)$stmt->affected_rows;
        $stmt->close();
        return max($deleted, 0);
    }

    // Generates ONE overdue notification per document that has stayed in
    // Processing for more than 10 consecutive days. Documents already
    // Dispatched (or otherwise not Processing) are ignored automatically.
    function checkOverdueDocuments($conn) {
        $stmt = $conn->prepare(
            "SELECT
                 d.id,
                 d.reference_no,
                 d.title,
                 DATEDIFF(NOW(), COALESCE((
                     SELECT MAX(du.created_at)
                     FROM document_updates du
                     WHERE du.document_id = d.id AND du.status = 'Processing'
                 ), d.created_at)) AS processing_days
             FROM documents d
             WHERE d.status = 'Processing'
               AND COALESCE((
                    SELECT MAX(du.created_at)
                    FROM document_updates du
                    WHERE du.document_id = d.id AND du.status = 'Processing'
               ), d.created_at) < (NOW() - INTERVAL 10 DAY)"
        );
        if (!$stmt) return 0;
        $stmt->execute();
        $result = $stmt->get_result();

        $created = 0;
        while ($row = $result->fetch_assoc()) {
            $days = max((int)$row['processing_days'], 1);
            $message = "Reference No. " . $row['reference_no'] . "\nhas been in Processing for " . $days . " days.";
            if (createNotification(
                $conn,
                'overdue',
                'Overdue Document',
                $message,
                (int)$row['id'],
                $row['reference_no'],
                'high'
            )) {
                $created++;
            }
        }
        $stmt->close();
        return $created;
    }
}
?>
