<?php
/**
 * Dashboard Analytics API
 * Provides analytics data for the dashboard
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

// Helper function to safely query with error handling
function safeQuery($pdo, $query, $default = 0) {
    try {
        $result = $pdo->query($query);
        if ($result) {
            return $result->fetchColumn() ?: $default;
        }
    } catch (PDOException $e) {
        error_log("Dashboard query error: " . $e->getMessage() . " | Query: " . $query);
        return $default;
    }
    return $default;
}

// Helper function to check if table exists
function tableExists($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    // Get total subscribers (with table check)
    $totalSubscribers = 0;
    if (tableExists($pdo, 'subscriptions')) {
        $totalSubscribers = safeQuery($pdo, "SELECT COUNT(*) FROM subscriptions WHERE status = 'active'", 0);
    }
    
    // Get subscribers change this week
    $subscriberChange = 0;
    if (tableExists($pdo, 'subscriptions')) {
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $subscriberChange = safeQuery($pdo, "
            SELECT COUNT(*) FROM subscriptions 
            WHERE status = 'active' AND created_at >= '$weekAgo'
        ", 0);
    }
    
    // Get notifications sent today
    $notificationsToday = 0;
    if (tableExists($pdo, 'notification_logs')) {
        $today = date('Y-m-d');
        $notificationsToday = safeQuery($pdo, "
            SELECT COUNT(*) FROM notification_logs 
            WHERE DATE(sent_at) = '$today'
        ", 0);
    }
    
    // Get success rate
    $successRate = 100;
    if (tableExists($pdo, 'notification_logs')) {
        $totalNotifications = safeQuery($pdo, "SELECT COUNT(*) FROM notification_logs", 0);
        $successfulNotifications = safeQuery($pdo, "
            SELECT COUNT(*) FROM notification_logs WHERE status = 'success'
        ", 0);
        $successRate = $totalNotifications > 0 ? round(($successfulNotifications / $totalNotifications) * 100) : 100;
    }
    
    // Get active weather alerts
    $weatherAlerts = 0;
    if (tableExists($pdo, 'automated_warnings')) {
        $weatherAlerts = safeQuery($pdo, "
            SELECT COUNT(*) FROM automated_warnings 
            WHERE source = 'pagasa' AND status = 'published'
        ", 0);
    }
    
    // Get active earthquake alerts
    $earthquakeAlerts = 0;
    if (tableExists($pdo, 'automated_warnings')) {
        $earthquakeAlerts = safeQuery($pdo, "
            SELECT COUNT(*) FROM automated_warnings 
            WHERE source = 'phivolcs' AND status = 'published'
        ", 0);
    }
    
    // Get pending messages (check both messages and chat_messages tables)
    $pendingMessages = 0;
    if (tableExists($pdo, 'messages')) {
        $pendingMessages = safeQuery($pdo, "
            SELECT COUNT(DISTINCT conversation_id) FROM messages 
            WHERE sender_type = 'citizen' AND read_at IS NULL
        ", 0);
    } elseif (tableExists($pdo, 'chat_messages')) {
        // Try chat_messages table as fallback
        $pendingMessages = safeQuery($pdo, "
            SELECT COUNT(DISTINCT conversation_id) FROM chat_messages 
            WHERE sender_type = 'citizen' AND is_read = 0
        ", 0);
    }
    
    // Get notifications chart data (last 7 days)
    $labels = [];
    $values = [];
    if (tableExists($pdo, 'notification_logs')) {
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime("-$i days"));
            $count = safeQuery($pdo, "
                SELECT COUNT(*) FROM notification_logs 
                WHERE DATE(sent_at) = '$date'
            ", 0);
            $labels[] = $dayName;
            $values[] = (int)$count;
        }
    } else {
        // Default empty data
        for ($i = 6; $i >= 0; $i--) {
            $dayName = date('D', strtotime("-$i days"));
            $labels[] = $dayName;
            $values[] = 0;
        }
    }
    
    // Get channels distribution
    $smsCount = 0;
    $emailCount = 0;
    $paCount = 0;
    if (tableExists($pdo, 'notification_logs')) {
        $smsCount = safeQuery($pdo, "SELECT COUNT(*) FROM notification_logs WHERE channel = 'sms'", 0);
        $emailCount = safeQuery($pdo, "SELECT COUNT(*) FROM notification_logs WHERE channel = 'email'", 0);
        $paCount = safeQuery($pdo, "SELECT COUNT(*) FROM notification_logs WHERE channel = 'pa'", 0);
    }
    
    // Get recent activity
    $recentActivity = [];
    
    // Recent notifications
    if (tableExists($pdo, 'notification_logs')) {
        try {
            $stmt = $pdo->query("
                SELECT CONCAT('Notification sent via ', channel) as title, 
                       sent_at as time, 'notification' as type
                FROM notification_logs 
                ORDER BY sent_at DESC 
                LIMIT 5
            ");
            $notifications = $stmt->fetchAll();
            foreach ($notifications as $notif) {
                $recentActivity[] = [
                    'title' => $notif['title'],
                    'time' => timeAgo($notif['time']),
                    'type' => 'notification'
                ];
            }
        } catch (PDOException $e) {
            error_log("Dashboard recent notifications error: " . $e->getMessage());
        }
    }
    
    // Recent messages (check both tables)
    if (tableExists($pdo, 'messages')) {
        try {
            $stmt = $pdo->query("
                SELECT CONCAT('New message from citizen') as title, 
                       sent_at as time, 'message' as type
                FROM messages 
                WHERE sender_type = 'citizen'
                ORDER BY sent_at DESC 
                LIMIT 3
            ");
            $messages = $stmt->fetchAll();
            foreach ($messages as $msg) {
                $recentActivity[] = [
                    'title' => $msg['title'],
                    'time' => timeAgo($msg['time']),
                    'type' => 'message'
                ];
            }
        } catch (PDOException $e) {
            error_log("Dashboard recent messages error: " . $e->getMessage());
        }
    } elseif (tableExists($pdo, 'chat_messages')) {
        try {
            $stmt = $pdo->query("
                SELECT CONCAT('New message from citizen') as title, 
                       created_at as time, 'message' as type
                FROM chat_messages 
                WHERE sender_type = 'citizen'
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            $messages = $stmt->fetchAll();
            foreach ($messages as $msg) {
                $recentActivity[] = [
                    'title' => $msg['title'],
                    'time' => timeAgo($msg['time']),
                    'type' => 'message'
                ];
            }
        } catch (PDOException $e) {
            error_log("Dashboard recent chat messages error: " . $e->getMessage());
        }
    }
    
    // Sort by time and limit to 10
    usort($recentActivity, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $recentActivity = array_slice($recentActivity, 0, 10);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_subscribers' => (int)$totalSubscribers,
            'subscriber_change' => (int)$subscriberChange,
            'notifications_today' => (int)$notificationsToday,
            'success_rate' => (int)$successRate,
            'weather_alerts' => (int)$weatherAlerts,
            'earthquake_alerts' => (int)$earthquakeAlerts,
            'pending_messages' => (int)$pendingMessages
        ],
        'charts' => [
            'notifications' => [
                'labels' => $labels,
                'values' => $values
            ],
            'channels' => [
                'labels' => ['SMS', 'Email', 'PA System'],
                'values' => [(int)$smsCount, (int)$emailCount, (int)$paCount]
            ]
        ],
        'activity' => $recentActivity
    ]);
    
} catch (PDOException $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard data',
        'stats' => [
            'total_subscribers' => 0,
            'subscriber_change' => 0,
            'notifications_today' => 0,
            'success_rate' => 0,
            'weather_alerts' => 0,
            'earthquake_alerts' => 0,
            'pending_messages' => 0
        ],
        'charts' => [
            'notifications' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [0, 0, 0, 0, 0, 0, 0]
            ],
            'channels' => [
                'labels' => ['SMS', 'Email', 'PA System'],
                'values' => [0, 0, 0]
            ]
        ],
        'activity' => []
    ]);
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>

