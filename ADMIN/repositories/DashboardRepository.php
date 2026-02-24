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
     * Check if a column exists in a table
     *
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    private function columnExists($tableName, $columnName) {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
            return $stmt && $stmt->rowCount() > 0;
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
     * Build deterministic day labels and ISO dates for charting.
     *
     * @param int $days Number of days to include
     * @return array{labels: array<int,string>, dates: array<int,string>}
     */
    private function buildRecentDateSeries($days = 7) {
        $days = max(1, (int)$days);
        $labels = [];
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime("-{$i} days");
            $labels[] = date('D', $ts);
            $dates[] = date('Y-m-d', $ts);
        }
        return ['labels' => $labels, 'dates' => $dates];
    }

    /**
     * Current active/open conversations across the system.
     *
     * @return int
     */
    public function getActiveConversationsCount() {
        if (!$this->tableExists('conversations')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM conversations
            WHERE status IN ('active', 'open', 'in_progress', 'waiting_user')
        ", 0);
    }

    /**
     * Queue backlog for two-way communication routing.
     *
     * @return int
     */
    public function getPendingQueueCount() {
        if (!$this->tableExists('chat_queue')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM chat_queue
            WHERE status IN ('pending', 'open')
        ", 0);
    }

    /**
     * Count inbound citizen/user threads in the last N hours.
     *
     * @param int $hours
     * @return int
     */
    public function getRecentCitizenMessagesCount($hours = 24) {
        $hours = max(1, (int)$hours);

        if ($this->tableExists('chat_messages')) {
            return (int)$this->safeQuery("
                SELECT COUNT(DISTINCT conversation_id) FROM chat_messages
                WHERE sender_type IN ('user', 'citizen')
                  AND created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
            ", 0);
        }

        if ($this->tableExists('messages') && $this->columnExists('messages', 'conversation_id')) {
            $timeColumn = null;
            if ($this->columnExists('messages', 'sent_at')) {
                $timeColumn = 'sent_at';
            } elseif ($this->columnExists('messages', 'created_at')) {
                $timeColumn = 'created_at';
            }

            if ($this->columnExists('messages', 'sender_type') && $timeColumn !== null) {
                return (int)$this->safeQuery("
                    SELECT COUNT(DISTINCT conversation_id) FROM messages
                    WHERE sender_type IN ('citizen', 'user')
                      AND {$timeColumn} >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                ", 0);
            }

            if ($timeColumn !== null) {
                return (int)$this->safeQuery("
                    SELECT COUNT(DISTINCT conversation_id) FROM messages
                    WHERE {$timeColumn} >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                ", 0);
            }
        }

        return 0;
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
        if ($this->tableExists('chat_messages')) {
            return (int)$this->safeQuery("
                SELECT COUNT(DISTINCT conversation_id) FROM chat_messages 
                WHERE sender_type IN ('user', 'citizen') AND COALESCE(is_read, 0) = 0
            ", 0);
        } elseif ($this->tableExists('messages') && $this->columnExists('messages', 'conversation_id')) {
            if ($this->columnExists('messages', 'sender_type') && $this->columnExists('messages', 'read_at')) {
                return (int)$this->safeQuery("
                    SELECT COUNT(DISTINCT conversation_id) FROM messages 
                    WHERE sender_type IN ('citizen', 'user') AND read_at IS NULL
                ", 0);
            }

            // Legacy fallback when sender/read columns are unavailable.
            return (int)$this->safeQuery("
                SELECT COUNT(DISTINCT conversation_id) FROM messages 
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
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
        $series = $this->buildRecentDateSeries(7);
        $values = array_fill(0, count($series['dates']), 0);

        if ($this->tableExists('notification_logs')) {
            foreach ($series['dates'] as $idx => $date) {
                $values[$idx] = (int)$this->safeQuery("
                    SELECT COUNT(*) FROM notification_logs
                    WHERE DATE(sent_at) = '{$date}'
                ", 0);
            }
        }

        return ['labels' => $series['labels'], 'values' => $values];
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
     * Daily incident source trend for dashboard charts.
     *
     * @param int $days
     * @return array{labels: array<int,string>, weather: array<int,int>, earthquake: array<int,int>}
     */
    public function getIncidentTrendData($days = 7) {
        $series = $this->buildRecentDateSeries($days);
        $weather = [];
        $earthquake = [];

        foreach ($series['dates'] as $date) {
            $weather[] = $this->getDomainWarningCountByDate('weather', $date);
            $earthquake[] = $this->getDomainWarningCountByDate('earthquake', $date);
        }

        return [
            'labels' => $series['labels'],
            'weather' => $weather,
            'earthquake' => $earthquake
        ];
    }

    /**
     * End-to-end operational flow snapshot used by static dashboard graph.
     *
     * @return array{labels: array<int,string>, values: array<int,int>}
     */
    public function getEndToEndFlowData() {
        $received = $this->getRecentCitizenMessagesCount(24);
        $pendingQueue = $this->getPendingQueueCount();
        $activeConversations = $this->getActiveConversationsCount();
        $alertsSent = $this->getNotificationsToday();
        $successRate = max(0, min(100, (int)$this->getSuccessRate()));
        $delivered = (int)round(($alertsSent * $successRate) / 100);

        return [
            'labels' => [
                'Citizen reports (24h)',
                'Queue backlog',
                'In-progress conversations',
                'Alerts sent today',
                'Estimated delivered'
            ],
            'values' => [
                $received,
                $pendingQueue,
                $activeConversations,
                $alertsSent,
                $delivered
            ]
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
        if ($this->tableExists('chat_messages')) {
            try {
                $stmt = $this->pdo->query("
                    SELECT CONCAT('New message from citizen') as title, 
                           created_at as time, 'message' as type
                    FROM chat_messages 
                    WHERE sender_type IN ('citizen', 'user')
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
        } elseif ($this->tableExists('messages') && $this->columnExists('messages', 'sent_at')) {
            try {
                if ($this->columnExists('messages', 'sender_type')) {
                    $stmt = $this->pdo->query("
                        SELECT CONCAT('New message from citizen') as title, 
                               sent_at as time, 'message' as type
                        FROM messages 
                        WHERE sender_type IN ('citizen', 'user')
                        ORDER BY sent_at DESC 
                        LIMIT 3
                    ");
                } else {
                    $stmt = $this->pdo->query("
                        SELECT CONCAT('Recent chat message') as title, 
                               sent_at as time, 'message' as type
                        FROM messages 
                        ORDER BY sent_at DESC 
                        LIMIT 3
                    ");
                }
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
        }
        
        // Sort by time and limit
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, $limit);
    }

    /**
     * End-to-end module status overview used by dashboard monitor cards.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleStatusOverview() {
        $subscribers = $this->getTotalSubscribers();
        $notificationsToday = $this->getNotificationsToday();
        $successRate = $this->getSuccessRate();
        $pendingMessages = $this->getPendingMessages();
        $activeConversations = $this->getActiveConversationsCount();
        $pendingQueue = $this->getPendingQueueCount();

        $weather24h = $this->getDomainWarningCount('weather', 24);
        $earthquake24h = $this->getDomainWarningCount('earthquake', 24);
        $pendingAutoWarnings = $this->getPendingAutomatedWarningsCount();
        $weatherIntegration = $this->getIntegrationHealth('pagasa');
        $earthquakeIntegration = $this->getIntegrationHealth('phivolcs');
        $activeLanguages = $this->getActiveLanguagesCount();
        $auditToday = $this->getAuditEventsTodayCount();
        $pendingApprovals = $this->getPendingApprovalsCount();

        $massStatus = 'ok';
        if ($notificationsToday === 0) {
            $massStatus = 'info';
        } elseif ($successRate < 85) {
            $massStatus = 'critical';
        } elseif ($successRate < 95) {
            $massStatus = 'warning';
        }

        $chatStatus = 'ok';
        if ($pendingQueue > 10 || $pendingMessages > 20) {
            $chatStatus = 'critical';
        } elseif ($pendingQueue > 3 || $pendingMessages > 5) {
            $chatStatus = 'warning';
        } elseif ($activeConversations === 0) {
            $chatStatus = 'info';
        }

        $autoWarnStatus = 'ok';
        if ($pendingAutoWarnings > 25) {
            $autoWarnStatus = 'critical';
        } elseif ($pendingAutoWarnings > 5) {
            $autoWarnStatus = 'warning';
        } elseif (($weather24h + $earthquake24h) === 0) {
            $autoWarnStatus = 'info';
        }

        $weatherStatus = ($weatherIntegration['enabled'] && $weatherIntegration['configured']) ? 'ok' : 'critical';
        $earthquakeStatus = $earthquakeIntegration['enabled'] ? 'ok' : 'warning';
        $subsStatus = $subscribers > 0 ? 'ok' : 'info';
        $languageStatus = $activeLanguages >= 2 ? 'ok' : 'warning';
        $approvalsStatus = $pendingApprovals > 0 ? 'warning' : 'ok';
        $auditStatus = $auditToday > 0 ? 'ok' : 'info';

        return [
            [
                'key' => 'mass_notification',
                'name' => 'Mass Notification',
                'icon' => 'fa-paper-plane',
                'status' => $massStatus,
                'metric' => $notificationsToday,
                'metric_label' => 'Sent today',
                'secondary' => $successRate . '% success rate',
                'route' => 'mass-notification.php'
            ],
            [
                'key' => 'two_way_communication',
                'name' => 'Two-Way Communication',
                'icon' => 'fa-comments',
                'status' => $chatStatus,
                'metric' => $pendingMessages,
                'metric_label' => 'Unread threads',
                'secondary' => $pendingQueue . ' queued | ' . $activeConversations . ' active',
                'route' => 'two-way-communication.php'
            ],
            [
                'key' => 'automated_warnings',
                'name' => 'Automated Warnings',
                'icon' => 'fa-bolt',
                'status' => $autoWarnStatus,
                'metric' => $weather24h + $earthquake24h,
                'metric_label' => 'Events (24h)',
                'secondary' => $pendingAutoWarnings . ' pending processing',
                'route' => 'automated-warnings.php'
            ],
            [
                'key' => 'weather_monitoring',
                'name' => 'Weather Monitoring',
                'icon' => 'fa-cloud-rain',
                'status' => $weatherStatus,
                'metric' => $weather24h,
                'metric_label' => 'Weather alerts (24h)',
                'secondary' => $weatherIntegration['summary'],
                'route' => 'weather-monitoring.php'
            ],
            [
                'key' => 'earthquake_monitoring',
                'name' => 'Earthquake Monitoring',
                'icon' => 'fa-mountain',
                'status' => $earthquakeStatus,
                'metric' => $earthquake24h,
                'metric_label' => 'Seismic alerts (24h)',
                'secondary' => $earthquakeIntegration['summary'],
                'route' => 'earthquake-monitoring.php'
            ],
            [
                'key' => 'citizen_subscriptions',
                'name' => 'Citizen Subscriptions',
                'icon' => 'fa-users',
                'status' => $subsStatus,
                'metric' => $subscribers,
                'metric_label' => 'Active subscribers',
                'secondary' => 'Monitoring enrollment and delivery reach',
                'route' => 'citizen-subscriptions.php'
            ],
            [
                'key' => 'multilingual_support',
                'name' => 'Multilingual Support',
                'icon' => 'fa-language',
                'status' => $languageStatus,
                'metric' => $activeLanguages,
                'metric_label' => 'Active languages',
                'secondary' => 'Translation and language delivery pipeline',
                'route' => 'general-settings.php'
            ],
            [
                'key' => 'admin_approvals',
                'name' => 'Admin Approvals',
                'icon' => 'fa-user-check',
                'status' => $approvalsStatus,
                'metric' => $pendingApprovals,
                'metric_label' => 'Pending approvals',
                'secondary' => $pendingApprovals > 0 ? 'Review pending admin requests' : 'No pending admin approvals',
                'route' => 'admin-approvals.php'
            ],
            [
                'key' => 'audit_trail',
                'name' => 'Audit Trail',
                'icon' => 'fa-clipboard-list',
                'status' => $auditStatus,
                'metric' => $auditToday,
                'metric_label' => 'Events logged today',
                'secondary' => 'Operational accountability and traceability',
                'route' => 'audit-trail.php'
            ]
        ];
    }

    /**
     * Count domain-specific warnings for the last N hours.
     */
    private function getDomainWarningCount($domain, $hours = 24) {
        $hours = max(1, (int)$hours);

        if ($this->tableExists('automated_warnings')) {
            if ($domain === 'weather') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM automated_warnings
                    WHERE received_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                      AND (
                        LOWER(COALESCE(source, '')) IN ('pagasa', 'weather', 'openweather')
                        OR LOWER(COALESCE(type, '')) IN ('weather', 'rain', 'flood', 'storm', 'typhoon')
                      )
                ", 0);
            }

            if ($domain === 'earthquake') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM automated_warnings
                    WHERE received_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                      AND (
                        LOWER(COALESCE(source, '')) IN ('phivolcs', 'earthquake')
                        OR LOWER(COALESCE(type, '')) IN ('earthquake', 'seismic', 'tsunami')
                      )
                ", 0);
            }
        }

        if ($this->tableExists('alerts')) {
            if ($domain === 'weather') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM alerts
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                      AND (
                        category_id = 1
                        OR LOWER(COALESCE(category, '')) LIKE '%weather%'
                      )
                ", 0);
            }

            if ($domain === 'earthquake') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM alerts
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                      AND (
                        category_id = 2
                        OR LOWER(COALESCE(category, '')) LIKE '%earthquake%'
                      )
                ", 0);
            }
        }

        return 0;
    }

    /**
     * Count domain-specific warnings for a single date (YYYY-MM-DD).
     *
     * @param string $domain
     * @param string $date
     * @return int
     */
    private function getDomainWarningCountByDate($domain, $date) {
        $safeDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) === 1 ? (string)$date : date('Y-m-d');

        if ($this->tableExists('automated_warnings')) {
            if ($domain === 'weather') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM automated_warnings
                    WHERE DATE(received_at) = '{$safeDate}'
                      AND (
                        LOWER(COALESCE(source, '')) IN ('pagasa', 'weather', 'openweather')
                        OR LOWER(COALESCE(type, '')) IN ('weather', 'rain', 'flood', 'storm', 'typhoon')
                      )
                ", 0);
            }

            if ($domain === 'earthquake') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM automated_warnings
                    WHERE DATE(received_at) = '{$safeDate}'
                      AND (
                        LOWER(COALESCE(source, '')) IN ('phivolcs', 'earthquake')
                        OR LOWER(COALESCE(type, '')) IN ('earthquake', 'seismic', 'tsunami')
                      )
                ", 0);
            }
        }

        if ($this->tableExists('alerts')) {
            if ($domain === 'weather') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM alerts
                    WHERE DATE(created_at) = '{$safeDate}'
                      AND (
                        category_id = 1
                        OR LOWER(COALESCE(category, '')) LIKE '%weather%'
                      )
                ", 0);
            }

            if ($domain === 'earthquake') {
                return (int)$this->safeQuery("
                    SELECT COUNT(*) FROM alerts
                    WHERE DATE(created_at) = '{$safeDate}'
                      AND (
                        category_id = 2
                        OR LOWER(COALESCE(category, '')) LIKE '%earthquake%'
                      )
                ", 0);
            }
        }

        return 0;
    }

    private function getPendingAutomatedWarningsCount() {
        if (!$this->tableExists('automated_warnings')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM automated_warnings 
            WHERE LOWER(COALESCE(status, '')) IN ('pending', 'queued')
        ", 0);
    }

    private function getIntegrationHealth($source) {
        if (!$this->tableExists('integration_settings')) {
            return [
                'enabled' => false,
                'configured' => false,
                'summary' => 'Integration settings table unavailable'
            ];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT enabled, api_key, last_sync
                FROM integration_settings
                WHERE source = :source
                LIMIT 1
            ");
            $stmt->execute([':source' => $source]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'enabled' => false,
                    'configured' => false,
                    'summary' => 'Integration not configured'
                ];
            }

            $enabled = (int)($row['enabled'] ?? 0) === 1;
            // PHIVOLCS typically does not require API key in this project.
            $configured = $source === 'phivolcs' ? true : !empty($row['api_key']);
            $lastSync = !empty($row['last_sync']) ? ('Last sync: ' . $row['last_sync']) : 'No recent sync';

            if (!$enabled) {
                return ['enabled' => false, 'configured' => $configured, 'summary' => 'Integration disabled'];
            }

            if (!$configured) {
                return ['enabled' => true, 'configured' => false, 'summary' => 'API key not set'];
            }

            return ['enabled' => true, 'configured' => true, 'summary' => $lastSync];
        } catch (PDOException $e) {
            error_log('Dashboard integration health error: ' . $e->getMessage());
            return [
                'enabled' => false,
                'configured' => false,
                'summary' => 'Integration check failed'
            ];
        }
    }

    private function getActiveLanguagesCount() {
        if (!$this->tableExists('supported_languages')) {
            return 0;
        }
        return (int)$this->safeQuery("
            SELECT COUNT(*) FROM supported_languages
            WHERE COALESCE(is_active, 1) = 1
        ", 0);
    }

    private function getPendingApprovalsCount() {
        if (!$this->tableExists('admin_user')) {
            return 0;
        }
        if ($this->columnExists('admin_user', 'status')) {
            return (int)$this->safeQuery("
                SELECT COUNT(*) FROM admin_user
                WHERE status = 'pending_approval'
            ", 0);
        }
        return 0;
    }

    private function getAuditEventsTodayCount() {
        $today = date('Y-m-d');
        if ($this->tableExists('admin_activity_logs') && $this->columnExists('admin_activity_logs', 'created_at')) {
            return (int)$this->safeQuery("
                SELECT COUNT(*) FROM admin_activity_logs
                WHERE DATE(created_at) = '{$today}'
            ", 0);
        }
        if ($this->tableExists('audit_log') && $this->columnExists('audit_log', 'created_at')) {
            return (int)$this->safeQuery("
                SELECT COUNT(*) FROM audit_log
                WHERE DATE(created_at) = '{$today}'
            ", 0);
        }
        return 0;
    }
}
