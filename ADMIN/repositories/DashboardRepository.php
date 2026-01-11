<?php
/**
 * Dashboard Repository
 * Handles all database operations for dashboard statistics
 * 
 * @package ADMIN\Repositories
 */

require_once __DIR__ . '/../api/db_connect.php';

class DashboardRepository {
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool True if table exists, false otherwise
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$tableName'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Safe query execution with error handling
     * 
     * @param string $query SQL query
     * @param mixed $default Default value to return on error
     * @return mixed Query result or default value
     */
    private function safeQuery($query, $default = 0) {
        try {
            $result = $this->pdo->query($query);
            if ($result) {
                return $result->fetchColumn() ?: $default;
            }
        } catch (PDOException $e) {
            error_log("Dashboard query error: " . $e->getMessage() . " | Query: " . $query);
            return $default;
        }
        return $default;
    }
    
    /**
     * Get total active subscribers count
     * 
     * @return int Total subscribers
     */
    public function getTotalSubscribers() {
        if (!$this->tableExists('subscriptions')) {
            return 0;
        }
        return (int)$this->safeQuery("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'", 0);
    }
    
    /**
     * Get subscriber change count (new subscribers this week)
     * 
     * @return int New subscribers count
     */
    public function getSubscriberChange() {
        if (!$this->tableExists('subscriptions')) {
            return 0;
        }
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM subscriptions 
            WHERE status = 'active' AND created_at >= '$weekAgo'
        ", 0);
    }
    
    /**
     * Get notifications sent today count
     * 
     * @return int Notifications count
     */
    public function getNotificationsToday() {
        if (!$this->tableExists('notification_logs')) {
            return 0;
        }
        $today = date('Y-m-d');
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM notification_logs 
            WHERE DATE(sent_at) = '$today'
        ", 0);
    }
    
    /**
     * Get notification success rate percentage
     * 
     * @return int Success rate (0-100)
     */
    public function getSuccessRate() {
        if (!$this->tableExists('notification_logs')) {
            return 100;
        }
        $total = (int)$this->safeQuery("SELECT COUNT(*) FROM notification_logs", 0);
        $successful = (int)$this->safeQuery("
            SELECT COUNT(*) FROM notification_logs WHERE status = 'success'
        ", 0);
        return $total > 0 ? round(($successful / $total) * 100) : 100;
    }
    
    /**
     * Get active weather alerts count
     * 
     * @return int Weather alerts count
     */
    public function getWeatherAlerts() {
        if (!$this->tableExists('automated_warnings')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM automated_warnings 
            WHERE source = 'pagasa' AND status = 'published'
        ", 0);
    }
    
    /**
     * Get active earthquake alerts count
     * 
     * @return int Earthquake alerts count
     */
    public function getEarthquakeAlerts() {
        if (!$this->tableExists('automated_warnings')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM automated_warnings 
            WHERE source = 'phivolcs' AND status = 'published'
        ", 0);
    }
    
    /**
     * Get pending messages count
     * 
     * @return int Pending messages count
     */
    public function getPendingMessages() {
        if ($this->tableExists('messages')) {
            return (int)$this->safeQuery("
                SELECT COUNT(DISTINCT conversation_id) FROM messages 
                WHERE sender_type = 'citizen' AND read_at IS NULL
            ", 0);
        } elseif ($this->tableExists('chat_messages')) {
            return (int)$this->safeQuery("
                SELECT COUNT(DISTINCT conversation_id) FROM chat_messages 
                WHERE sender_type = 'citizen' AND is_read = 0
            ", 0);
        }
        return 0;
    }
    
    /**
     * Get notification chart data for last 7 days
     * 
     * @return array Array with 'labels' and 'values' keys
     */
    public function getNotificationChartData() {
        $labels = [];
        $values = [];
        
        if ($this->tableExists('notification_logs')) {
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dayName = date('D', strtotime("-$i days"));
                $count = (int)$this->safeQuery("
                    SELECT COUNT(*) FROM notification_logs 
                    WHERE DATE(sent_at) = '$date'
                ", 0);
                $labels[] = $dayName;
                $values[] = $count;
            }
        } else {
            // Default empty data
            for ($i = 6; $i >= 0; $i--) {
                $dayName = date('D', strtotime("-$i days"));
                $labels[] = $dayName;
                $values[] = 0;
            }
        }
        
        return ['labels' => $labels, 'values' => $values];
    }
    
    /**
     * Get channel distribution counts
     * 
     * @return array Array with 'sms', 'email', 'pa' keys
     */
    public function getChannelDistribution() {
        if (!$this->tableExists('notification_logs')) {
            return ['sms' => 0, 'email' => 0, 'pa' => 0];
        }
        
        return [
            'sms' => (int)$this->safeQuery("SELECT COUNT(*) FROM notification_logs WHERE channel = 'sms'", 0),
            'email' => (int)$this->safeQuery("SELECT COUNT(*) FROM notification_logs WHERE channel = 'email'", 0),
            'pa' => (int)$this->safeQuery("SELECT COUNT(*) FROM notification_logs WHERE channel = 'pa'", 0)
        ];
    }
    
    /**
     * Get recent activity
     * 
     * @param int $limit Number of activities to return
     * @return array Array of activity records
     */
    public function getRecentActivity($limit = 10) {
        $activities = [];
        
        // Recent notifications
        if ($this->tableExists('notification_logs')) {
            try {
                $stmt = $this->pdo->query("
                    SELECT CONCAT('Notification sent via ', channel) as title, 
                           sent_at as time, 'notification' as type
                    FROM notification_logs 
                    ORDER BY sent_at DESC 
                    LIMIT 5
                ");
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($notifications as $notif) {
                    $activities[] = [
                        'title' => $notif['title'],
                        'time' => $notif['time'],
                        'type' => 'notification'
                    ];
                }
            } catch (PDOException $e) {
                error_log("Dashboard recent notifications error: " . $e->getMessage());
            }
        }
        
        // Recent messages
        if ($this->tableExists('messages')) {
            try {
                $stmt = $this->pdo->query("
                    SELECT CONCAT('New message from citizen') as title, 
                           sent_at as time, 'message' as type
                    FROM messages 
                    WHERE sender_type = 'citizen'
                    ORDER BY sent_at DESC 
                    LIMIT 3
                ");
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($messages as $msg) {
                    $activities[] = [
                        'title' => $msg['title'],
                        'time' => $msg['time'],
                        'type' => 'message'
                    ];
                }
            } catch (PDOException $e) {
                error_log("Dashboard recent messages error: " . $e->getMessage());
            }
        } elseif ($this->tableExists('chat_messages')) {
            try {
                $stmt = $this->pdo->query("
                    SELECT CONCAT('New message from citizen') as title, 
                           created_at as time, 'message' as type
                    FROM chat_messages 
                    WHERE sender_type = 'citizen'
                    ORDER BY created_at DESC 
                    LIMIT 3
                ");
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($messages as $msg) {
                    $activities[] = [
                        'title' => $msg['title'],
                        'time' => $msg['time'],
                        'type' => 'message'
                    ];
                }
            } catch (PDOException $e) {
                error_log("Dashboard recent chat messages error: " . $e->getMessage());
            }
        }
        
        // Sort by time and limit
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, $limit);
    }
}