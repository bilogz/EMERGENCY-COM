<?php
/**
 * UNIFIED ALERTS ENDPOINT
 * 
 * GET: Retrieve list of community alerts with translation and category details.
 * POST: Dispatch a new community alert / emergency broadcast across multiple channels.
 */

// Enable error logging, prevent displaying output directly
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/auth.php';

// Verify PDO and dynamic tables helpers
function getAlertsTableName(PDO $pdo): string {
    $candidates = ['alerts', 'alerts_runtime', 'alerts_runtime_fallback'];
    foreach ($candidates as $candidate) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($candidate));
            if ($stmt && $stmt->fetch()) {
                $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
                return $candidate;
            }
        } catch (Throwable $e) {
            // continue
        }
    }
    return 'alerts';
}

function checkTableColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

$alertsTable = getAlertsTableName($pdo);
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET alerts retrieval
if ($method === 'GET') {
    try {
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'active';
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
        $lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
        $category = isset($_GET['category']) && $_GET['category'] !== '' && $_GET['category'] !== 'all' ? trim($_GET['category']) : null;
        $timeFilter = isset($_GET['time_filter']) && in_array($_GET['time_filter'], ['24h', 'week', 'month', 'year', 'all'], true) ? $_GET['time_filter'] : '24h';
        
        $hasSeverity = checkTableColumn($pdo, $alertsTable, 'severity');
        $hasSource = checkTableColumn($pdo, $alertsTable, 'source');
        $hasType = checkTableColumn($pdo, $alertsTable, 'type');
        $hasCategoryCol = checkTableColumn($pdo, $alertsTable, 'category');
        $hasCategoryTable = false;
        
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'alert_categories'");
            if ($stmt && $stmt->fetch()) {
                $hasCategoryTable = true;
            }
        } catch (Throwable $e) {}

        // Construct query
        $query = "SELECT a.id, a.title, a.message, a.content, ";
        $query .= ($hasSeverity ? "a.severity" : "'' AS severity") . ", ";
        $query .= ($hasSource ? "a.source" : "'' AS source") . ", ";
        $query .= ($hasType ? "a.type" : "'' AS type") . ", ";
        $query .= ($hasCategoryCol ? "a.category" : "'' AS category") . ", ";
        $query .= "a.status, a.created_at, a.updated_at, ";
        
        if ($hasCategoryTable) {
            $query .= "COALESCE(ac.name, 'General') as category_name,
                       COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
                       COALESCE(ac.color, '#95a5a6') as category_color
                       FROM {$alertsTable} a
                       LEFT JOIN alert_categories ac ON a.category_id = ac.id
                       WHERE a.status = :status";
        } else {
            $query .= "'General' as category_name,
                       'fa-exclamation-triangle' as category_icon,
                       '#95a5a6' as category_color
                       FROM {$alertsTable} a
                       WHERE a.status = :status";
        }
        
        $params = [':status' => $status];
        
        if ($lastId > 0) {
            $query .= " AND a.id > :last_id";
            $params[':last_id'] = $lastId;
        } elseif ($timeFilter !== 'all') {
            switch ($timeFilter) {
                case '24h':
                    $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                    break;
                case 'week':
                    $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                    break;
            }
        }
        
        if ($category) {
            if ($hasCategoryTable) {
                $query .= " AND (ac.name = :category OR a.category = :category)";
            } else {
                $query .= " AND a.category = :category";
            }
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY a.id DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        if ($lastId > 0) {
            $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
        }
        if ($category) {
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logApiAccess($pdo, $deptName, '/api/alerts.php', 'GET', 200, "Retrieved " . count($alerts) . " alerts (status=$status, filter=$timeFilter)");
        sendJsonResponse(true, 'Alerts fetched successfully.', ['alerts' => $alerts]);
        
    } catch (PDOException $e) {
        logApiAccess($pdo, $deptName, '/api/alerts.php', 'GET', 500, "Database error: " . $e->getMessage());
        sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
    }
}

// Handle POST alerts broadcast creation
elseif ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? '');
        $categoryId = isset($input['category_id']) ? (int)$input['category_id'] : null;
        $severity = trim($input['severity'] ?? 'Medium');
        $audienceType = trim($input['audience_type'] ?? 'all');
        $barangay = trim($input['barangay'] ?? '');
        $role = trim($input['role'] ?? '');
        
        $channelsRaw = $input['channels'] ?? ['push'];
        $channels = is_array($channelsRaw) ? $channelsRaw : array_filter(array_map('trim', explode(',', $channelsRaw)));
        
        $targetLat = isset($input['target_lat']) ? (float)$input['target_lat'] : null;
        $targetLng = isset($input['target_lng']) ? (float)$input['target_lng'] : null;
        $radiusM = isset($input['radius_m']) ? (int)$input['radius_m'] : 1000;
        $targetAddress = trim($input['target_address'] ?? '');
        
        $weatherSignal = isset($input['weather_signal']) ? (int)$input['weather_signal'] : null;
        $fireLevel = isset($input['fire_level']) ? (int)$input['fire_level'] : null;

        if (empty($title) || empty($message)) {
            sendJsonResponse(false, 'Bad Request: Title and Message fields are required.', [], 400);
        }

        // Validate severity
        $allowedSeverity = ['Low', 'Medium', 'High', 'Critical'];
        if (!in_array($severity, $allowedSeverity)) {
            $severity = 'Medium';
        }

        // Validate audience filters and load target users
        $recipientIds = [];
        $recipientParams = [];
        $recipientQuery = "SELECT id, phone, email FROM users WHERE status = 'active'";

        if ($audienceType === 'barangay' && !empty($barangay)) {
            $recipientQuery .= " AND barangay = ?";
            $recipientParams[] = $barangay;
        } elseif ($audienceType === 'role' && !empty($role)) {
            $recipientQuery .= " AND user_type = ?";
            $recipientParams[] = $role;
        } elseif ($audienceType === 'location' && $targetLat !== null && $targetLng !== null) {
            // Try checking if user_locations exists
            try {
                $locStmt = $pdo->query("SHOW TABLES LIKE 'user_locations'");
                if ($locStmt && $locStmt->fetch()) {
                    // Filter using Haversine formula (radius in meters)
                    $recipientQuery = "SELECT DISTINCT u.id, u.phone, u.email 
                                       FROM users u 
                                       INNER JOIN user_locations ul ON ul.user_id = u.id AND ul.is_current = 1
                                       WHERE u.status = 'active'
                                       AND (6371000 * 2 * ASIN(SQRT(
                                            POWER(SIN(RADIANS(ul.latitude - ?)/2), 2) +
                                            COS(RADIANS(?)) * COS(RADIANS(ul.latitude)) *
                                            POWER(SIN(RADIANS(ul.longitude - ?)/2), 2)
                                       ))) <= ?";
                    $recipientParams = [$targetLat, $targetLat, $targetLng, $radiusM];
                }
            } catch (Throwable $e) {}
        }

        $userStmt = $pdo->prepare($recipientQuery);
        $userStmt->execute($recipientParams);
        $recipients = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        // 1. Insert notification_logs entry
        $channelStr = implode(',', $channels);
        $audienceStr = $audienceType;
        if ($audienceType === 'barangay') $audienceStr .= ": $barangay";
        if ($audienceType === 'role') $audienceStr .= ": $role";
        if ($audienceType === 'location') $audienceStr .= ": within {$radiusM}m of {$targetLat},{$targetLng}";

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS notification_logs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    channel VARCHAR(100) NOT NULL,
                    message TEXT NOT NULL,
                    recipients TEXT NOT NULL,
                    priority VARCHAR(20) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    sent_by VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $logStmt = $pdo->prepare("
                INSERT INTO notification_logs (channel, message, recipients, priority, status, sent_by, ip_address)
                VALUES (?, ?, ?, ?, 'pending', ?, ?)
            ");
            $logStmt->execute([
                $channelStr,
                $message,
                $audienceStr,
                $severity,
                'api_' . $deptName,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $logId = $pdo->lastInsertId();
        } catch (PDOException $e) {
            $logId = 0; // Fallback if logging fails
        }

        // 2. Insert alert entry
        $hasSeverityCol = checkTableColumn($pdo, $alertsTable, 'severity');
        $hasWeatherSignalCol = checkTableColumn($pdo, $alertsTable, 'weather_signal');
        $hasFireLevelCol = checkTableColumn($pdo, $alertsTable, 'fire_level');
        $hasSourceCol = checkTableColumn($pdo, $alertsTable, 'source');
        $hasLocationCol = checkTableColumn($pdo, $alertsTable, 'location');
        $hasLatitudeCol = checkTableColumn($pdo, $alertsTable, 'latitude');
        $hasLongitudeCol = checkTableColumn($pdo, $alertsTable, 'longitude');
        $hasCategoryCol = checkTableColumn($pdo, $alertsTable, 'category');

        $alertCols = ['title', 'message', 'content', 'category_id', 'status'];
        $alertVals = [$title, $message, $message, $categoryId, 'active'];
        
        if ($hasSeverityCol) {
            $alertCols[] = 'severity';
            $alertVals[] = strtolower($severity);
        }
        if ($hasWeatherSignalCol) {
            $alertCols[] = 'weather_signal';
            $alertVals[] = $weatherSignal;
        }
        if ($hasFireLevelCol) {
            $alertCols[] = 'fire_level';
            $alertVals[] = $fireLevel;
        }
        if ($hasSourceCol) {
            $alertCols[] = 'source';
            $alertVals[] = 'department_api';
        }
        if ($hasCategoryCol) {
            // resolve category name if category_id matches
            $catName = 'General';
            if ($categoryId > 0) {
                try {
                    $catStmt = $pdo->prepare("SELECT name FROM alert_categories WHERE id = ? LIMIT 1");
                    $catStmt->execute([$categoryId]);
                    $catName = $catStmt->fetchColumn() ?: 'General';
                } catch (Throwable $e) {}
            }
            $alertCols[] = 'category';
            $alertVals[] = $catName;
        }
        if ($audienceType === 'location' && $targetLat !== null && $targetLng !== null) {
            if ($hasLatitudeCol) {
                $alertCols[] = 'latitude';
                $alertVals[] = $targetLat;
            }
            if ($hasLongitudeCol) {
                $alertCols[] = 'longitude';
                $alertVals[] = $targetLng;
            }
            if ($hasLocationCol) {
                $alertCols[] = 'location';
                $alertVals[] = ($targetAddress !== '' ? $targetAddress : ($targetLat . ',' . $targetLng));
            }
        }

        $colList = implode(', ', $alertCols);
        $placeholderList = implode(', ', array_fill(0, count($alertVals), '?'));
        
        $pdo->beginTransaction();
        
        $alertSql = "INSERT INTO {$alertsTable} ({$colList}) VALUES ({$placeholderList})";
        $alertStmt = $pdo->prepare($alertSql);
        $alertStmt->execute($alertVals);
        $alertId = $pdo->lastInsertId();

        // 3. Persist Alert Recipients mapping
        if ($alertId > 0 && !empty($recipients)) {
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS alert_recipients (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        alert_id BIGINT UNSIGNED NOT NULL,
                        user_id BIGINT UNSIGNED NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_alert_user (alert_id, user_id),
                        INDEX idx_alert_id (alert_id),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $mappingSql = "INSERT IGNORE INTO alert_recipients (alert_id, user_id) VALUES (?, ?)";
                $mappingStmt = $pdo->prepare($mappingSql);
                foreach ($recipients as $recipient) {
                    $mappingStmt->execute([$alertId, $recipient['id']]);
                }
            } catch (Throwable $e) {
                error_log('API broadcast alert_recipients mapping failed: ' . $e->getMessage());
            }

            // 4. Push to Notification Queue for channels
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS notification_queue (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        log_id BIGINT UNSIGNED NOT NULL,
                        recipient_id BIGINT UNSIGNED NULL,
                        recipient_type VARCHAR(40) NOT NULL DEFAULT 'unknown',
                        recipient_value VARCHAR(255) NOT NULL DEFAULT '',
                        channel VARCHAR(20) NOT NULL DEFAULT 'push',
                        title VARCHAR(255) NOT NULL DEFAULT '',
                        message TEXT NULL,
                        status VARCHAR(20) NOT NULL DEFAULT 'pending',
                        delivery_status VARCHAR(20) NULL,
                        error_message TEXT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        processed_at DATETIME NULL,
                        delivered_at DATETIME NULL,
                        INDEX idx_queue_status_created (status, created_at),
                        INDEX idx_queue_log_id (log_id),
                        INDEX idx_queue_channel_status (channel, status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $queueSql = "INSERT INTO notification_queue (log_id, recipient_id, recipient_type, recipient_value, channel, title, message, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                $queueStmt = $pdo->prepare($queueSql);
                
                foreach ($recipients as $recipient) {
                    foreach ($channels as $channel) {
                        $value = '';
                        $type = 'unknown';
                        if ($channel === 'push') {
                            $type = 'user_id';
                            $value = (string)$recipient['id'];
                        } elseif ($channel === 'sms' && !empty($recipient['phone'])) {
                            $type = 'phone';
                            $value = $recipient['phone'];
                        } elseif ($channel === 'email' && !empty($recipient['email'])) {
                            $type = 'email';
                            $value = $recipient['email'];
                        }
                        
                        if ($type !== 'unknown' && !empty($value)) {
                            $queueStmt->execute([
                                $logId,
                                $recipient['id'],
                                $type,
                                $value,
                                $channel,
                                $title,
                                $message
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('API broadcast notification queue insertion error: ' . $e->getMessage());
            }
        }

        $pdo->commit();
        
        logApiAccess($pdo, $deptName, '/api/alerts.php', 'POST', 201, "Created Alert ID $alertId targeting " . count($recipients) . " users");
        sendJsonResponse(true, 'Alert broadcast created and queued successfully.', [
            'alert_id' => $alertId,
            'log_id' => $logId,
            'recipients_targeted' => count($recipients)
        ], 201);
        
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        logApiAccess($pdo, $deptName, '/api/alerts.php', 'POST', 500, "Exception: " . $e->getMessage());
        sendJsonResponse(false, 'Alert broadcast creation failed: ' . $e->getMessage(), [], 500);
    }
} else {
    sendJsonResponse(false, 'Method Not Allowed.', [], 405);
}
