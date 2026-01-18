<?php
/**
 * Mass Notification System API
 * Handle SMS, Email, and PA System notifications
 * Uses translations from alert_translations table based on user language preferences
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
        require_once __DIR__ . '/../repositories/AlertRepository.php';
        $alertRepository = new AlertRepository($pdo);
        
        $categoryId = null;
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            $categoryId = intval($_POST['category_id']);
        } else {
            // Try to find or create a "General" category
            $categoryId = $alertRepository->findOrGetDefaultCategoryId('General');
        }
        
        // Insert alert into alerts table
        $alertId = $alertRepository->create($title, $message, $message, $categoryId, 'active');
        
        // Initialize translation helper
        $translationHelper = new AlertTranslationHelper($pdo);
        
        // Get all subscribers based on recipient selection (using repository)
        require_once __DIR__ . '/../repositories/SubscriberRepository.php';
        $subscriberRepository = new SubscriberRepository($pdo);
        
        $subscribers = [];
        if (is_array($recipients)) {
            $subscribers = $subscriberRepository->getByRecipients($recipients);
        }
        
        $sentCount = 0;
        $translationStats = ['total' => 0, 'translated' => 0, 'english' => 0];
        
        // Send notification to each subscriber with automatic translation
        foreach ($subscribers as $subscriber) {
            $userId = $subscriber['user_id'];
            $userChannels = explode(',', $subscriber['channels'] ?? '');
            $userChannels = array_map('trim', $userChannels);
            
            // Get user's preferred language
            $userLanguage = $subscriber['preferred_language'] ?? 'en';
            
            // Get translated alert for user's preferred language (from database)
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
            if ($translatedAlert['language'] !== 'en') {
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
                $translationNote = " ({$translationStats['translated']} translated)";
            }
            logAdminActivity($adminId, 'send_mass_notification', 
                "Sent {$channel} notification to {$sentCount} recipient(s) via {$source}. Priority: {$priority}.{$translationNote}");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully.',
            'alert_id' => $alertId,
            'sent_count' => $sentCount,
            'translation_stats' => $translationStats,
            'note' => $translationStats['translated'] > 0 ? 
                "Alerts translated to {$translationStats['translated']} different languages" : 
                'All alerts sent in English'
        ]);
    } catch (PDOException $e) {
        error_log("Mass Notification Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
} elseif ($action === 'list') {
    try {
        $stmt = $pdo->query("
            SELECT id, channel, message, recipients, status, sent_at, response
            FROM notification_logs
            ORDER BY sent_at DESC
            LIMIT 50
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure status and progress are calculated correctly for UI
        foreach ($notifications as &$notif) {
            $stats = json_decode($notif['response'] ?? '', true);
            if ($stats) {
                $notif['progress'] = $stats['progress'] ?? 0;
                $notif['stats'] = $stats;
            } else {
                // If no stats yet, it's either pending in script or just finished queuing (sent)
                // In both cases, background worker hasn't started yet.
                $notif['progress'] = ($notif['status'] === 'completed' || $notif['status'] === 'success') ? 100 : 0;
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (PDOException $e) {
        error_log("List Notifications Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
    }
} elseif ($action === 'get_options') {
    try {
        // Fetch Barangays
        $bStmt = $pdo->query("SELECT DISTINCT barangay FROM users WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay");
        $barangays = $bStmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch Categories with visual metadata
        $cStmt = $pdo->query("SELECT id, name, icon, color FROM alert_categories ORDER BY name");
        $categories = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Templates
        $tStmt = $pdo->query("SELECT t.*, c.name as category_name FROM notification_templates t LEFT JOIN alert_categories c ON t.category_id = c.id ORDER BY t.created_at DESC");
        $templates = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'barangays' => $barangays,
            'categories' => $categories,
            'templates' => $templates
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

