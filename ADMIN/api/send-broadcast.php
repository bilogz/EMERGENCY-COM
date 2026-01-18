<?php
/**
 * Send Broadcast Controller
 * Handles audience selection and inserts jobs into the notification queue
 */

// 1. Prevent any accidental output (warnings, notices) from breaking JSON
ob_start();

// 2. Set strict JSON header
header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'db_connect.php';
    require_once 'activity_logger.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 3. Authentication check
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        throw new Exception('Unauthorized access denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $adminId = $_SESSION['admin_user_id'] ?? 0;

    // 4. Gather and Sanitize Data
    $audienceType = $_POST['audience_type'] ?? 'all';
    $barangay = $_POST['barangay'] ?? '';
    $role = $_POST['role'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    
    $channels = $_POST['channels'] ?? []; 
    if (is_string($channels)) {
        $channels = explode(',', $channels);
    }
    $channels = array_filter(array_map('trim', $channels));

    $severity = $_POST['severity'] ?? 'Medium';
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if (empty($channels) || empty($title) || empty($body)) {
        $missing = [];
        if (empty($channels)) $missing[] = "channels";
        if (empty($title)) $missing[] = "title";
        if (empty($body)) $missing[] = "body";
        throw new Exception('Required fields missing: ' . implode(', ', $missing));
    }

    // 5. Build Recipient Query
    $sql = "SELECT u.id, u.name, u.email, u.phone, d.fcm_token 
            FROM users u 
            LEFT JOIN user_devices d ON u.id = d.user_id AND d.is_active = 1
            WHERE u.status = 'active'";
    $params = [];

    if ($audienceType === 'barangay' && !empty($barangay)) {
        $sql .= " AND u.barangay = ?";
        $params[] = $barangay;
    } elseif ($audienceType === 'role' && !empty($role)) {
        $sql .= " AND u.user_type = ?";
        $params[] = $role;
    } elseif ($audienceType === 'topic' && !empty($categoryId)) {
        $sql .= " AND u.id IN (SELECT user_id FROM user_subscriptions WHERE category_id = ? AND is_active = 1)";
        $params[] = $categoryId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If PA is not selected, we need at least one recipient
    if (empty($recipients) && !in_array('pa', $channels)) {
        throw new Exception('No active recipients found for the selected audience.');
    }

    // 6. Insert Pending Log Entry
    $channelStr = implode(',', $channels);
    $audienceStr = $audienceType . ($barangay ? ": $barangay" : "") . ($role ? ": $role" : "") . ($categoryId ? ": Cat $categoryId" : "");
    
    $logStmt = $pdo->prepare("
        INSERT INTO notification_logs (channel, message, recipients, priority, status, sent_at, sent_by, ip_address)
        VALUES (?, ?, ?, ?, 'pending', NOW(), ?, ?)
    ");
    $logStmt->execute([
        $channelStr,
        $body,
        $audienceStr,
        strtolower($severity),
        'admin_' . $adminId,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    $logId = $pdo->lastInsertId();

    // 7. Queue Dispatch Jobs
    $queueCount = 0;
    foreach ($recipients as $recipient) {
        foreach ($channels as $channel) {
            $value = '';
            $type = '';
            
            if ($channel === 'sms' && !empty($recipient['phone'])) {
                $value = $recipient['phone'];
                $type = 'phone';
            } elseif ($channel === 'email' && !empty($recipient['email'])) {
                $value = $recipient['email'];
                $type = 'email';
            } elseif ($channel === 'push' && !empty($recipient['fcm_token'])) {
                $value = $recipient['fcm_token'];
                $type = 'fcm_token';
            }

            if (!empty($value)) {
                $qStmt = $pdo->prepare("
                    INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $qStmt->execute([$logId, $recipient['id'], $type, $value, $channel, $title, $body]);
                $queueCount++;
            }
        }
    }

    // Handle Public Address System
    if (in_array('pa', $channels)) {
        $qStmt = $pdo->prepare("
            INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status)
            VALUES (?, NULL, 'system', 'pa_system', 'pa', ?, ?, 'pending')
        ");
        $qStmt->execute([$logId, $title, $body]);
        $queueCount++;
    }

    // 8. Create Entry in Alerts table for global monitoring
    $aStmt = $pdo->prepare("
        INSERT INTO alerts (title, message, content, category_id, severity, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    $aStmt->execute([$title, $body, $body, $categoryId, $severity]);

    // 9. Update Log Status to 'sent' (Queued successfully)
    // Note: 'updated_at' is omitted as it does not exist in the schema.
    $updateStmt = $pdo->prepare("UPDATE notification_logs SET status = 'sent' WHERE id = ?");
    $updateStmt->execute([$logId]);

    // 10. Audit Activity
    logAdminActivity($adminId, 'mass_notification_queued', "Queued $queueCount messages for $audienceStr. Log ID: $logId");

    // 11. Final Clean Output
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Notification successfully queued.',
        'log_id' => $logId,
        'recipients' => count($recipients),
        'queued_jobs' => $queueCount
    ]);
    exit;

} catch (Exception $e) {
    // Attempt to update log status to 'failed' if logId was created
    if (isset($logId) && $logId) {
        try {
            $pdo->prepare("UPDATE notification_logs SET status = 'failed' WHERE id = ?")->execute([$logId]);
        } catch (PDOException $innerEx) {
            // Silence inner exception
        }
    }

    // Discard any accidental buffered output
    if (ob_get_length()) ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Dispatch error: ' . $e->getMessage()
    ]);
    exit;
}
