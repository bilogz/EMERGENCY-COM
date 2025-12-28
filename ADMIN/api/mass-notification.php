<?php
/**
 * Mass Notification System API
 * Handle SMS, Email, and PA System notifications
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';

session_start();

$action = $_GET['action'] ?? 'send';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $channel = $_POST['channel'] ?? '';
    $message = $_POST['message'] ?? '';
    $recipients = $_POST['recipients'] ?? [];
    $priority = $_POST['priority'] ?? 'medium';
    // Source of the alert (e.g. application, pagasa, phivolcs, other)
    $source = $_POST['source'] ?? 'application';
    
    if (empty($channel) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Channel and message are required.']);
        exit;
    }
    
    try {
        // In a real implementation, you would integrate with SMS gateway, email service, and PA system
        // For now, we'll just log it to the database
        
        $stmt = $pdo->prepare("
            INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW(), 'admin', ?)
        ");
        
        $recipientsStr = is_array($recipients) ? implode(',', $recipients) : $recipients;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // We store the alert source in the `recipient` column so we don't need a schema change.
        $stmt->execute([$channel, $message, $source, $recipientsStr, $priority, $ipAddress]);
        $notificationId = $pdo->lastInsertId();
        
        // Simulate sending (in production, call actual SMS/Email/PA services)
        $status = 'success'; // or 'failed' based on actual service response
        
        $stmt = $pdo->prepare("UPDATE notification_logs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $notificationId]);
        
        // Log admin activity
        $adminId = $_SESSION['admin_user_id'] ?? null;
        if ($adminId) {
            $recipientCount = is_array($recipients) ? count($recipients) : (strpos($recipientsStr, ',') !== false ? substr_count($recipientsStr, ',') + 1 : 1);
            logAdminActivity($adminId, 'send_mass_notification', 
                "Sent {$channel} notification to {$recipientCount} recipient(s) via {$source}. Priority: {$priority}");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully.',
            'notification_id' => $notificationId
        ]);
    } catch (PDOException $e) {
        error_log("Mass Notification Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT id, channel, message, recipient, recipients, status, sent_at
            FROM notification_logs
            ORDER BY sent_at DESC
            LIMIT 100
        ");
        $notifications = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (PDOException $e) {
        error_log("List Notifications Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

