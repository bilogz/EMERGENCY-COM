<?php
/**
 * Push Notification Helper for Mobile App
 * Sends FCM push notifications to Android/iOS devices
 */

/**
 * Send push notification to mobile app users
 * @param int $userId User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $data Additional data payload
 * @param int $alertId Alert ID (optional)
 * @return bool Success status
 */
function sendPushNotification($userId, $title, $message, $data = [], $alertId = null) {
    global $pdo;
    
    if ($pdo === null) {
        error_log("Push Notification: Database connection not available");
        return false;
    }
    
    try {
        // Get user's active devices with FCM tokens
        $stmt = $pdo->prepare("
            SELECT device_id, fcm_token, push_token, device_type, device_name
            FROM user_devices
            WHERE user_id = ? AND is_active = 1
            AND (fcm_token IS NOT NULL OR push_token IS NOT NULL)
        ");
        $stmt->execute([$userId]);
        $devices = $stmt->fetchAll();
        
        if (empty($devices)) {
            error_log("Push Notification: No active devices found for user $userId");
            return false;
        }
        
        $successCount = 0;
        $fcmServerKey = getFCMServerKey();
        
        if (empty($fcmServerKey)) {
            error_log("Push Notification: FCM Server Key not configured");
            return false;
        }
        
        foreach ($devices as $device) {
            $token = $device['fcm_token'] ?: $device['push_token'];
            if (empty($token)) {
                continue;
            }
            
            // Prepare notification payload
            $notification = [
                'title' => $title,
                'body' => $message,
                'sound' => 'default',
                'badge' => 1
            ];
            
            // Prepare data payload
            $dataPayload = array_merge([
                'alert_id' => $alertId,
                'type' => 'alert',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ], $data);
            
            // FCM API endpoint
            $url = 'https://fcm.googleapis.com/fcm/send';
            
            // Prepare FCM message
            $fcmMessage = [
                'to' => $token,
                'notification' => $notification,
                'data' => $dataPayload,
                'priority' => 'high'
            ];
            
            // Send via cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: key=' . $fcmServerKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmMessage));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['success']) && $responseData['success'] > 0) {
                    $successCount++;
                    error_log("Push Notification: Sent successfully to device {$device['device_id']} for user $userId");
                    
                    // Log notification
                    logPushNotification($userId, $device['device_id'], $title, $message, $alertId, 'success');
                } else {
                    error_log("Push Notification: Failed for device {$device['device_id']}: " . ($responseData['results'][0]['error'] ?? 'Unknown error'));
                    logPushNotification($userId, $device['device_id'], $title, $message, $alertId, 'failed');
                }
            } else {
                error_log("Push Notification: HTTP error $httpCode for device {$device['device_id']}");
                logPushNotification($userId, $device['device_id'], $title, $message, $alertId, 'failed');
            }
        }
        
        return $successCount > 0;
        
    } catch (Exception $e) {
        error_log("Push Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send push notification to multiple users
 * @param array $userIds Array of user IDs
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $data Additional data payload
 * @param int $alertId Alert ID (optional)
 * @return int Number of successful notifications sent
 */
function sendBulkPushNotifications($userIds, $title, $message, $data = [], $alertId = null) {
    $successCount = 0;
    
    foreach ($userIds as $userId) {
        if (sendPushNotification($userId, $title, $message, $data, $alertId)) {
            $successCount++;
        }
    }
    
    return $successCount;
}

/**
 * Get FCM Server Key from configuration
 * @return string FCM Server Key
 */
function getFCMServerKey() {
    // Try to get from config.local.php
    if (file_exists(__DIR__ . '/config.local.php')) {
        $config = require __DIR__ . '/config.local.php';
        if (isset($config['FCM_SERVER_KEY']) && !empty($config['FCM_SERVER_KEY'])) {
            return $config['FCM_SERVER_KEY'];
        }
    }
    
    // Try to get from environment variable
    if (isset($_ENV['FCM_SERVER_KEY']) && !empty($_ENV['FCM_SERVER_KEY'])) {
        return $_ENV['FCM_SERVER_KEY'];
    }
    
    // Try to get from .env file
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'FCM_SERVER_KEY=') === 0) {
                return trim(substr($line, strlen('FCM_SERVER_KEY=')), '"\'');
            }
        }
    }
    
    return '';
}

/**
 * Log push notification
 */
function logPushNotification($userId, $deviceId, $title, $message, $alertId, $status) {
    global $pdo;
    
    if ($pdo === null) {
        return;
    }
    
    try {
        // Check if notification_logs table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'notification_logs'");
        if ($stmt->rowCount() === 0) {
            return; // Table doesn't exist, skip logging
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notification_logs 
            (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
            VALUES ('push', ?, ?, ?, 'high', ?, NOW(), 'system', '127.0.0.1')
        ");
        $recipient = "User $userId (Device: $deviceId)";
        $stmt->execute([$message, $recipient, $recipient, $status]);
    } catch (Exception $e) {
        error_log("Failed to log push notification: " . $e->getMessage());
    }
}
?>

