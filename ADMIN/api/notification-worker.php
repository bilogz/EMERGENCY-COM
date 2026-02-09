<?php
/**
 * Notification Background Worker
 * Processes the notification_queue in batches
 */

require_once 'db_connect.php';

// Batch size per run
$batchSize = 100;

try {
    // Ensure minimal columns exist for progress tracking (best-effort, backward compatible)
    try {
        $logColsStmt = $pdo->query("SHOW COLUMNS FROM notification_logs");
        $logCols = $logColsStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('response', $logCols, true)) {
            try { $pdo->exec("ALTER TABLE notification_logs ADD COLUMN response TEXT NULL"); } catch (PDOException $e) {}
        }
        if (!in_array('status', $logCols, true)) {
            try { $pdo->exec("ALTER TABLE notification_logs ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"); } catch (PDOException $e) {}
        }
    } catch (PDOException $e) {
        // ignore
    }

    $queueCols = [];
    $queueHasCreatedAt = false;
    $queueHasDeliveryStatus = false;
    $queueHasErrorMessage = false;
    $queueHasProcessedAt = false;
    $queueHasDeliveredAt = false;
    try {
        $qColsStmt = $pdo->query("SHOW COLUMNS FROM notification_queue");
        $queueCols = $qColsStmt->fetchAll(PDO::FETCH_COLUMN);
        $queueHasCreatedAt = in_array('created_at', $queueCols, true);
        $queueHasDeliveryStatus = in_array('delivery_status', $queueCols, true);
        $queueHasErrorMessage = in_array('error_message', $queueCols, true);
        $queueHasProcessedAt = in_array('processed_at', $queueCols, true);
        $queueHasDeliveredAt = in_array('delivered_at', $queueCols, true);
    } catch (PDOException $e) {
        $queueCols = [];
    }

    // Get pending messages
    $stmt = $pdo->prepare(
        "SELECT * FROM notification_queue 
        WHERE status = 'pending' 
        ORDER BY " . ($queueHasCreatedAt ? "created_at" : "id") . " ASC 
        LIMIT ?"
    );
    $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        // echo "No pending jobs.\n";
        exit;
    }

    foreach ($jobs as $job) {
        $success = false;
        $error = null;

        try {
            switch ($job['channel']) {
                case 'sms':
                    $success = sendSMS($job['recipient_value'], $job['message']);
                    break;
                case 'email':
                    $success = sendEmail($job['recipient_value'], $job['title'], $job['message']);
                    break;
                case 'push':
                    $success = sendFCM($job['recipient_value'], [
                        'title' => $job['title'],
                        'body' => $job['message']
                    ]);
                    break;
                case 'pa':
                    $success = broadcastPA($job['message']);
                    break;
                default:
                    $error = "Unknown channel: " . $job['channel'];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        // Update job status (only touch columns that exist)
        $status = $success ? 'sent' : 'failed';
        $set = ["status = ?"];
        $params = [$status];

        if ($queueHasDeliveryStatus) {
            $set[] = "delivery_status = ?";
            $params[] = $success ? 'delivered' : 'failed';
        }
        if ($queueHasErrorMessage) {
            $set[] = "error_message = ?";
            $params[] = $error;
        }
        if ($queueHasProcessedAt) {
            $set[] = "processed_at = NOW()";
        }
        if ($queueHasDeliveredAt) {
            $set[] = "delivered_at = " . ($success ? "NOW()" : "NULL");
        }

        $updateStmt = $pdo->prepare("UPDATE notification_queue SET " . implode(', ', $set) . " WHERE id = ?");
        $params[] = $job['id'];
        $updateStmt->execute($params);

        // Update master log progress
        updateLogProgress($pdo, $job['log_id']);
    }

    // Avoid breaking JSON callers; output is only for CLI usage.
    if (php_sapi_name() === 'cli') {
        echo "Processed " . count($jobs) . " jobs.\n";
    }

} catch (PDOException $e) {
    error_log("Worker Error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Worker Error: " . $e->getMessage() . "\n";
    }
}

/**
 * Update the master notification_logs entry based on queue progress
 */
function updateLogProgress($pdo, $logId) {
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) as count 
        FROM notification_queue 
        WHERE log_id = ? 
        GROUP BY status"
    );
    $stmt->execute([$logId]);
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $pending = $stats['pending'] ?? 0;
    $sent = $stats['sent'] ?? 0;
    $failed = $stats['failed'] ?? 0;
    $total = $pending + $sent + $failed;

    $status = 'completed';
    if ($pending > 0) {
        $status = 'sending';
    }

    // Build backward-compatible update (response may not exist)
    $logCols = [];
    try {
        $logColsStmt = $pdo->query("SHOW COLUMNS FROM notification_logs");
        $logCols = $logColsStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $logCols = [];
    }
    
    $response = json_encode([
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'progress' => $total > 0 ? round((($sent + $failed) / $total) * 100) : 0
    ]);

    if (in_array('response', $logCols, true)) {
        $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = ?, response = ? WHERE id = ?");
        $updateStmt->execute([$status, $response, $logId]);
    } else {
        $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = ? WHERE id = ?");
        $updateStmt->execute([$status, $logId]);
    }
}

/**
 * PLACEHOLDER: SMS Dispatch
 */
function sendSMS($phone, $message) {
    // In production: Use Twilio, Infobip, or local SMS gateway
    // error_log("SMS to $phone: $message");
    return true; 
}

/**
 * PLACEHOLDER: Email Dispatch
 */
function sendEmail($email, $subject, $body) {
    // In production: Use PHPMailer or SMTP
    // error_log("Email to $email: $subject");
    return true;
}

/**
 * PLACEHOLDER: FCM Dispatch
 */
function sendFCM($token, $payload) {
    // In production: Use Firebase Admin SDK
    // error_log("Push to $token: " . json_encode($payload));
    return true;
}

/**
 * PLACEHOLDER: PA System Dispatch
 */
function broadcastPA($message) {
    // In production: Integration with IP-based PA system
    // error_log("PA Broadcast: $message");
    return true;
}
