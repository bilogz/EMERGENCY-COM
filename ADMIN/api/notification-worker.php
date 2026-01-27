<?php
/**
 * Notification Background Worker
 * Processes the notification_queue in batches
 */

require_once 'db_connect.php';

// Batch size per run
$batchSize = 100;

try {
    // Get pending messages
    $stmt = $pdo->prepare(
        "SELECT * FROM notification_queue 
        WHERE status = 'pending' 
        ORDER BY created_at ASC 
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

                // Update job status

                $status = $success ? 'sent' : 'failed';

                $deliveryStatus = $success ? 'delivered' : 'failed';

                $updateStmt = $pdo->prepare("

                    UPDATE notification_queue 

                    SET status = ?, delivery_status = ?, error_message = ?, processed_at = NOW(), delivered_at = " . ($success ? "NOW()" : "NULL") . " 

                    WHERE id = ?

                ");

                $updateStmt->execute([$status, $deliveryStatus, $error, $job['id']]);

        // Update master log progress
        updateLogProgress($pdo, $job['log_id']);
    }

    echo "Processed " . count($jobs) . " jobs.\n";

} catch (PDOException $e) {
    error_log("Worker Error: " . $e->getMessage());
    echo "Worker Error: " . $e->getMessage() . "\n";
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

    $updateStmt = $pdo->prepare(
        "UPDATE notification_logs 
        SET status = ?, 
            response = ? 
        WHERE id = ?"
    );
    
    $response = json_encode([
        'total' => $total,
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'progress' => $total > 0 ? round((($sent + $failed) / $total) * 100) : 0
    ]);

    $updateStmt->execute([$status, $response, $logId]);
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
