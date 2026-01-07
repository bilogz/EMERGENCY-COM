<?php
/**
 * Mass Notification System API
 * Handle SMS, Email, and PA System notifications
 * Automatically translates alerts using Gemini AI based on user language preferences
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'activity_logger.php';
require_once 'alert-translation-helper.php';

session_start();

$action = $_GET['action'] ?? 'send';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $channel = $_POST['channel'] ?? '';
    $message = $_POST['message'] ?? '';
    $recipients = $_POST['recipients'] ?? [];
    $priority = $_POST['priority'] ?? 'medium';
    $title = $_POST['title'] ?? 'Emergency Alert'; // Alert title
    // Source of the alert (e.g. application, pagasa, phivolcs, other)
    $source = $_POST['source'] ?? 'application';
    
    if (empty($channel) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Channel and message are required.']);
        exit;
    }
    
    try {
        $adminId = $_SESSION['admin_user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $recipientsStr = is_array($recipients) ? implode(',', $recipients) : $recipients;
        
        // Create alert entry in database for translation tracking
        $categoryId = null;
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            $categoryId = intval($_POST['category_id']);
        } else {
            // Try to find or create a "General" category
            $stmt = $pdo->prepare("SELECT id FROM alert_categories WHERE name = 'General' LIMIT 1");
            $stmt->execute();
            $cat = $stmt->fetch();
            if ($cat) {
                $categoryId = $cat['id'];
            }
        }
        
        // Insert alert into alerts table
        $stmt = $pdo->prepare("
            INSERT INTO alerts (title, message, content, category_id, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$title, $message, $message, $categoryId]);
        $alertId = $pdo->lastInsertId();
        
        // Initialize translation helper
        $translationHelper = new AlertTranslationHelper($pdo);
        
        // Get all subscribers based on recipient selection
        $subscribers = [];
        if (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                if ($recipient === 'all') {
                    // Get all active subscribers
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT s.user_id, s.channels, s.preferred_language,
                               u.name, u.email, u.phone
                        FROM subscriptions s
                        LEFT JOIN users u ON u.id = s.user_id
                        WHERE s.status = 'active'
                    ");
                    $stmt->execute();
                    $allSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $subscribers = array_merge($subscribers, $allSubs);
                } else {
                    // Get subscribers for specific category
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT s.user_id, s.channels, s.preferred_language,
                               u.name, u.email, u.phone
                        FROM subscriptions s
                        LEFT JOIN users u ON u.id = s.user_id
                        WHERE s.status = 'active'
                        AND (s.categories LIKE ? OR s.categories = 'all')
                    ");
                    $categoryPattern = "%{$recipient}%";
                    $stmt->execute([$categoryPattern]);
                    $catSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $subscribers = array_merge($subscribers, $catSubs);
                }
            }
        }
        
        // Remove duplicates
        $uniqueSubscribers = [];
        $seenUserIds = [];
        foreach ($subscribers as $sub) {
            $userId = $sub['user_id'];
            if (!in_array($userId, $seenUserIds)) {
                $uniqueSubscribers[] = $sub;
                $seenUserIds[] = $userId;
            }
        }
        $subscribers = $uniqueSubscribers;
        
        $sentCount = 0;
        $translationStats = ['total' => 0, 'translated' => 0, 'english' => 0];
        
        // Send notification to each subscriber with automatic translation
        foreach ($subscribers as $subscriber) {
            $userId = $subscriber['user_id'];
            $userChannels = explode(',', $subscriber['channels'] ?? '');
            $userChannels = array_map('trim', $userChannels);
            
            // Get user's preferred language
            $userLanguage = $subscriber['preferred_language'] ?? 'en';
            
            // Get translated alert for user's preferred language (auto-translates if needed)
            $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $userLanguage, $userId);
            
            if (!$translatedAlert) {
                // Fallback to original
                $translatedAlert = [
                    'title' => $title,
                    'message' => $message
                ];
            }
            
            // Track translation stats
            $translationStats['total']++;
            if ($translatedAlert['language'] !== 'en' && isset($translatedAlert['method']) && $translatedAlert['method'] === 'ai') {
                $translationStats['translated']++;
            } else {
                $translationStats['english']++;
            }
            
            // Format message based on channel
            $translatedMessage = '';
            if ($channel === 'sms') {
                $translatedMessage = $translatedAlert['title'] . "\n\n" . substr($translatedAlert['message'], 0, 140);
            } else {
                $translatedMessage = $translatedAlert['title'] . "\n\n" . $translatedAlert['message'];
            }
            
            // Check if user is subscribed to this channel
            if (!empty($userChannels) && !in_array($channel, $userChannels)) {
                continue; // User hasn't subscribed to this channel
            }
            
            // Send via appropriate channel
            if ($channel === 'sms' && !empty($subscriber['phone'])) {
                // Log SMS notification (try with alert_id and user_language, fallback if columns don't exist)
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address, alert_id, user_language)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['phone'], 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress,
                        $alertId,
                        $userLanguage
                    ]);
                } catch (PDOException $e) {
                    // Fallback if alert_id/user_language columns don't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['phone'], 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress
                    ]);
                }
                $sentCount++;
                
                // In production, call actual SMS gateway here
                // sendSMS($subscriber['phone'], $translatedMessage);
                
            } elseif ($channel === 'email' && !empty($subscriber['email'])) {
                // Log email notification
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address, alert_id, user_language)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['email'], 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress,
                        $alertId,
                        $userLanguage
                    ]);
                } catch (PDOException $e) {
                    // Fallback if alert_id/user_language columns don't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['email'], 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress
                    ]);
                }
                $sentCount++;
                
                // In production, call actual email service here
                // sendEmail($subscriber['email'], $subscriber['name'], $translatedAlert['title'], $translatedAlert['message']);
                
            } elseif ($channel === 'push') {
                // Send push notification to mobile app
                if (file_exists(__DIR__ . '/push-notification-helper.php')) {
                    require_once __DIR__ . '/push-notification-helper.php';
                    if (sendPushNotification($userId, $translatedAlert['title'], $translatedAlert['message'], ['alert_id' => $alertId], $alertId)) {
                        // Log push notification
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address, alert_id, user_language)
                                VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $channel, 
                                $translatedMessage, 
                                "User $userId", 
                                $recipientsStr, 
                                $priority, 
                                $adminId ? 'admin_' . $adminId : 'system',
                                $ipAddress,
                                $alertId,
                                $userLanguage
                            ]);
                        } catch (PDOException $e) {
                            // Fallback if columns don't exist
                            $stmt = $pdo->prepare("
                                INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
                                VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?)
                            ");
                            $stmt->execute([
                                $channel, 
                                $translatedMessage, 
                                "User $userId", 
                                $recipientsStr, 
                                $priority, 
                                $adminId ? 'admin_' . $adminId : 'system',
                                $ipAddress
                            ]);
                        }
                        $sentCount++;
                    }
                }
                
            } elseif ($channel === 'pa') {
                // PA System notification (log only)
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address, alert_id, user_language)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        'pa_system', 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress,
                        $alertId,
                        $userLanguage
                    ]);
                } catch (PDOException $e) {
                    // Fallback if alert_id/user_language columns don't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
                        VALUES (?, ?, ?, ?, ?, 'sent', NOW(), ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        'pa_system', 
                        $recipientsStr, 
                        $priority, 
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress
                    ]);
                }
                $sentCount++;
            }
        }
        
        // Log admin activity
        if ($adminId) {
            $translationNote = '';
            if ($translationStats['translated'] > 0) {
                $translationNote = " ({$translationStats['translated']} auto-translated using AI)";
            }
            logAdminActivity($adminId, 'send_mass_notification', 
                "Sent {$channel} notification to {$sentCount} recipient(s) via {$source}. Priority: {$priority}.{$translationNote}");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully with automatic translation.',
            'alert_id' => $alertId,
            'sent_count' => $sentCount,
            'translation_stats' => $translationStats,
            'note' => $translationStats['translated'] > 0 ? 
                "Alerts automatically translated to {$translationStats['translated']} different languages using Gemini AI" : 
                'All alerts sent in English'
        ]);
    } catch (PDOException $e) {
        error_log("Mass Notification Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
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

