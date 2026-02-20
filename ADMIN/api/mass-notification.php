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

/**
 * Normalize language code and map aliases.
 */
function normalizeAlertLanguage($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    // Keep simple BCP-47 style safety (letters, digits, hyphen, underscore)
    if ($lang !== '' && !preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
        return '';
    }
    return $lang;
}

/**
 * Resolve a usable alert categories table name.
 * Supports legacy fallback table used in some deployments.
 */
function mnResolveCategoriesTable(PDO $pdo): string {
    $candidates = ['alert_categories', 'alert_categories_catalog'];
    foreach ($candidates as $candidate) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($candidate));
            if ($stmt && $stmt->fetch()) {
                return $candidate;
            }
        } catch (PDOException $e) {
            // Try next candidate.
        }
    }
    return 'alert_categories';
}

/**
 * Ensure categories table exists and has minimum schema + seed rows.
 */
function mnEnsureCategoriesSchema(PDO $pdo, string $tableName): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            icon VARCHAR(120) NOT NULL DEFAULT 'fa-exclamation-triangle',
            description TEXT DEFAULT NULL,
            color VARCHAR(20) NOT NULL DEFAULT '#3a7675',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $colsStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
    $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

    $missing = [];
    if (!in_array('icon', $cols, true)) $missing['icon'] = "ALTER TABLE {$tableName} ADD COLUMN icon VARCHAR(120) NOT NULL DEFAULT 'fa-exclamation-triangle' AFTER name";
    if (!in_array('description', $cols, true)) $missing['description'] = "ALTER TABLE {$tableName} ADD COLUMN description TEXT DEFAULT NULL AFTER icon";
    if (!in_array('color', $cols, true)) $missing['color'] = "ALTER TABLE {$tableName} ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT '#3a7675' AFTER description";
    if (!in_array('status', $cols, true)) $missing['status'] = "ALTER TABLE {$tableName} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER color";
    if (!in_array('created_at', $cols, true)) $missing['created_at'] = "ALTER TABLE {$tableName} ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if (!in_array('updated_at', $cols, true)) $missing['updated_at'] = "ALTER TABLE {$tableName} ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP";

    foreach ($missing as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Continue; read path below still works with partial schemas.
        }
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
    if ($count === 0) {
        $seed = [
            ['Weather', 'fa-cloud-sun-rain', 'Weather advisories and rainfall alerts', '#3498db', 'active'],
            ['Earthquake', 'fa-mountain', 'Earthquake and aftershock notifications', '#e74c3c', 'active'],
            ['Fire', 'fa-fire', 'Fire incidents and evacuation notices', '#e67e22', 'active'],
            ['Flood', 'fa-water', 'Flood warnings and water level updates', '#1abc9c', 'active'],
            ['Bomb Threat', 'fa-bomb', 'Bomb threat and security alerts', '#9b59b6', 'active'],
            ['Health', 'fa-heartbeat', 'Health advisories and public health notices', '#2ecc71', 'active'],
            ['General', 'fa-bell', 'General advisories and announcements', '#3a7675', 'active'],
        ];
        $stmt = $pdo->prepare("
            INSERT INTO {$tableName} (name, icon, description, color, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        foreach ($seed as $row) {
            $stmt->execute($row);
        }
    }
}

/**
 * Load normalized categories for the dispatch wizard.
 */
function mnGetCategoriesForOptions(PDO $pdo): array {
    $tableName = mnResolveCategoriesTable($pdo);
    mnEnsureCategoriesSchema($pdo, $tableName);

    $colsStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
    $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
    $hasIcon = in_array('icon', $cols, true);
    $hasColor = in_array('color', $cols, true);
    $hasDescription = in_array('description', $cols, true);
    $hasStatus = in_array('status', $cols, true);

    $selectParts = ['id', 'name'];
    if ($hasIcon) $selectParts[] = 'icon';
    if ($hasColor) $selectParts[] = 'color';
    if ($hasDescription) $selectParts[] = 'description';

    $sql = "SELECT " . implode(', ', $selectParts) . " FROM {$tableName}";
    if ($hasStatus) {
        $sql .= " WHERE status = 'active'";
    }
    $sql .= " ORDER BY name";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$c) {
        if (!isset($c['icon']) || $c['icon'] === null || $c['icon'] === '') $c['icon'] = 'fa-exclamation-triangle';
        if (!isset($c['color']) || $c['color'] === null || $c['color'] === '') $c['color'] = '#4c8a89';
        if (!isset($c['description']) || $c['description'] === null) $c['description'] = '';
    }
    unset($c);

    return [
        'table' => $tableName,
        'categories' => $rows
    ];
}

/**
 * Resolve a writable notification logs table.
 * Uses runtime fallback when primary table exists but is corrupted.
 */
function mnEnsureNotificationLogsTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$tableName} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                channel VARCHAR(64) NOT NULL DEFAULT '',
                message TEXT NULL,
                recipient VARCHAR(255) NULL,
                recipients TEXT NULL,
                priority VARCHAR(32) NOT NULL DEFAULT 'medium',
                status VARCHAR(32) NOT NULL DEFAULT 'pending',
                sent_at DATETIME NULL,
                sent_by VARCHAR(120) NULL,
                ip_address VARCHAR(64) NULL,
                response LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status_sent_at (status, sent_at),
                INDEX idx_channel (channel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        return true;
    } catch (Throwable $e) {
        error_log("Mass Notification {$tableName} health check failed: " . $e->getMessage());
        return false;
    }
}

function mnResolveNotificationLogsTable(PDO $pdo): string {
    if (mnEnsureNotificationLogsTable($pdo, 'notification_logs')) {
        return 'notification_logs';
    }
    mnEnsureNotificationLogsTable($pdo, 'notification_logs_runtime');
    return 'notification_logs_runtime';
}

$action = $_GET['action'] ?? 'send';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $channel = $_POST['channel'] ?? '';
    $message = $_POST['message'] ?? '';
    $recipients = $_POST['recipients'] ?? [];
    $priority = $_POST['priority'] ?? 'medium';
    $title = $_POST['title'] ?? 'Emergency Alert'; // Alert title
    $severity = $_POST['severity'] ?? null;
    $weatherSignalRaw = $_POST['weather_signal'] ?? null;
    $fireLevelRaw = $_POST['fire_level'] ?? null;
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
        
        $weatherSignal = null;
        if ($weatherSignalRaw !== null && $weatherSignalRaw !== '') {
            $weatherSignal = (int)$weatherSignalRaw;
            if ($weatherSignal < 1 || $weatherSignal > 5) $weatherSignal = null;
        }

        $fireLevel = null;
        if ($fireLevelRaw !== null && $fireLevelRaw !== '') {
            $fireLevel = (int)$fireLevelRaw;
            if ($fireLevel < 1 || $fireLevel > 3) $fireLevel = null;
        }

        // Insert alert into alerts table
        $alertId = $alertRepository->create($title, $message, $message, $categoryId, 'active', $severity, $weatherSignal, $fireLevel);
        
        // Initialize translation helper
        $translationHelper = new AlertTranslationHelper($pdo);
        
        // Get all subscribers based on recipient selection (using repository)
        require_once __DIR__ . '/../repositories/SubscriberRepository.php';
        $subscriberRepository = new SubscriberRepository($pdo);
        
        $subscribers = [];
        if (is_array($recipients)) {
            $subscribers = $subscriberRepository->getByRecipients($recipients);
        }

        // Resolve each subscriber's language:
        // 1) subscriptions.preferred_language (already in result)
        // 2) SubscriberRepository fallback (user_preferences/users)
        // 3) default English
        $languagesToPreGenerate = [];
        foreach ($subscribers as $idx => $subscriber) {
            $userId = (int)($subscriber['user_id'] ?? 0);
            $userLanguage = normalizeAlertLanguage($subscriber['preferred_language'] ?? '');
            if ($userLanguage === '' && $userId > 0) {
                $userLanguage = normalizeAlertLanguage($subscriberRepository->getUserLanguage($userId));
            }
            if ($userLanguage === '') {
                $userLanguage = 'en';
            }
            $subscribers[$idx]['resolved_language'] = $userLanguage;
            if ($userLanguage !== 'en') {
                $languagesToPreGenerate[] = $userLanguage;
            }
        }

        // Warm up translation cache once per language for this alert.
        if (!empty($languagesToPreGenerate)) {
            $translationHelper->preGenerateTranslations($alertId, $title, $message, $languagesToPreGenerate);
        }
        
        $sentCount = 0;
        $translationStats = ['total' => 0, 'translated' => 0, 'english' => 0];
        
        // Send notification to each subscriber with automatic translation
        foreach ($subscribers as $subscriber) {
            $userId = $subscriber['user_id'];
            $userChannels = explode(',', $subscriber['channels'] ?? '');
            $userChannels = array_map('trim', $userChannels);
            
            // Get user's resolved preferred language
            $userLanguage = $subscriber['resolved_language'] ?? 'en';
            
            // Get translated alert for user's preferred language
            $translatedAlert = $translationHelper->getTranslatedAlert($alertId, $userLanguage, $title, $message);
            
            if (!$translatedAlert) {
                // Fallback to original
                $translatedAlert = [
                    'title' => $title,
                    'message' => $message,
                    'language' => 'en',
                    'method' => 'fallback_original'
                ];
            }
            if (!isset($translatedAlert['language']) || !$translatedAlert['language']) {
                $translatedAlert['language'] = 'en';
            }
            
            // Track translation stats
            $translationStats['total']++;
            if (strtolower((string)$translatedAlert['language']) !== 'en') {
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
        $logsTable = mnResolveNotificationLogsTable($pdo);
        // Backward compatible list: some installs may not have all columns (e.g., response)
        $colsStmt = $pdo->query("SHOW COLUMNS FROM {$logsTable}");
        $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

        $selectParts = [];
        $selectParts[] = in_array('id', $cols, true) ? 'id' : '0 as id';

        // Channel can be stored as channel or channels in older schemas.
        if (in_array('channel', $cols, true)) {
            $selectParts[] = 'channel';
        } elseif (in_array('channels', $cols, true)) {
            $selectParts[] = 'channels as channel';
        } else {
            $selectParts[] = "'' as channel";
        }

        // Message can be stored as message/content/body in older schemas.
        if (in_array('message', $cols, true)) {
            $selectParts[] = 'message';
        } elseif (in_array('content', $cols, true)) {
            $selectParts[] = 'content as message';
        } elseif (in_array('body', $cols, true)) {
            $selectParts[] = 'body as message';
        } else {
            $selectParts[] = "'' as message";
        }

        // Target can be stored as recipients or recipient.
        if (in_array('recipients', $cols, true)) {
            $selectParts[] = 'recipients';
        } elseif (in_array('recipient', $cols, true)) {
            $selectParts[] = 'recipient as recipients';
        } else {
            $selectParts[] = "'' as recipients";
        }

        $selectParts[] = in_array('status', $cols, true) ? 'status' : "'pending' as status";

        $hasSentAt = in_array('sent_at', $cols, true);
        $hasCreatedAt = in_array('created_at', $cols, true);
        $hasResponse = in_array('response', $cols, true);

        if ($hasSentAt) $selectParts[] = 'sent_at';
        elseif ($hasCreatedAt) $selectParts[] = 'created_at as sent_at';
        else $selectParts[] = "NULL as sent_at";

        if ($hasResponse) $selectParts[] = 'response';
        else $selectParts[] = "NULL as response";

        $orderBy = $hasSentAt ? 'sent_at' : ($hasCreatedAt ? 'created_at' : 'id');

        $stmt = $pdo->query("
            SELECT " . implode(', ', $selectParts) . "
            FROM {$logsTable}
            ORDER BY $orderBy DESC
            LIMIT 50
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Optional: compute queue stats when response column is missing/empty
        $queueStatsByLog = [];
        $logIds = array_values(array_filter(array_map(fn($n) => $n['id'] ?? null, $notifications)));
        if (!empty($logIds)) {
            try {
                // Check table exists (notification_queue)
                $tCheck = $pdo->query("SHOW TABLES LIKE 'notification_queue'");
                $hasQueue = $tCheck && $tCheck->rowCount() > 0;
                if ($hasQueue) {
                    $pdo->query("SELECT 1 FROM notification_queue LIMIT 1");
                }
            } catch (PDOException $e) {
                $hasQueue = false;
            }

            if (!empty($hasQueue)) {
                try {
                    $placeholders = implode(',', array_fill(0, count($logIds), '?'));
                    $qStmt = $pdo->prepare("
                        SELECT log_id, status, COUNT(*) as cnt
                        FROM notification_queue
                        WHERE log_id IN ($placeholders)
                        GROUP BY log_id, status
                    ");
                    $qStmt->execute($logIds);
                    while ($row = $qStmt->fetch(PDO::FETCH_ASSOC)) {
                        $lid = (string)$row['log_id'];
                        if (!isset($queueStatsByLog[$lid])) {
                            $queueStatsByLog[$lid] = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'total' => 0, 'progress' => 0];
                        }
                        $st = $row['status'] ?? 'pending';
                        $cnt = (int)($row['cnt'] ?? 0);
                        if (!isset($queueStatsByLog[$lid][$st])) $queueStatsByLog[$lid][$st] = 0;
                        $queueStatsByLog[$lid][$st] += $cnt;
                    }
                    foreach ($queueStatsByLog as $lid => &$st) {
                        $st['total'] = (int)($st['pending'] + $st['sent'] + $st['failed']);
                        $st['progress'] = $st['total'] > 0 ? (int)round((($st['sent'] + $st['failed']) / $st['total']) * 100) : 0;
                    }
                    unset($st);
                } catch (Throwable $qe) {
                    $queueStatsByLog = [];
                    error_log("Mass Notification queue stats degraded mode: " . $qe->getMessage());
                }
            }
        }
        
        // Ensure status and progress are calculated correctly for UI
        foreach ($notifications as &$notif) {
            $stats = json_decode($notif['response'] ?? '', true);
            if ($stats) {
                $notif['progress'] = $stats['progress'] ?? 0;
                $notif['stats'] = $stats;
            } else {
                // If response isn't available, try to compute from queue; else fallback.
                $qid = (string)($notif['id'] ?? '');
                if ($qid !== '' && isset($queueStatsByLog[$qid])) {
                    $notif['progress'] = $queueStatsByLog[$qid]['progress'] ?? 0;
                    $notif['stats'] = $queueStatsByLog[$qid];
                    // Match worker semantics: pending -> sending, else completed
                    if (($queueStatsByLog[$qid]['pending'] ?? 0) > 0) $notif['status'] = 'sending';
                    else $notif['status'] = 'completed';
                } else {
                    // If no stats yet, it's either pending in script or just finished queuing (sent)
                    $notif['progress'] = ($notif['status'] === 'completed' || $notif['status'] === 'success') ? 100 : 0;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'meta' => ['table' => $logsTable]
        ]);
    } catch (PDOException $e) {
        error_log("List Notifications Error: " . $e->getMessage());
        // Degraded mode: keep UI functional even when notification_logs table is unhealthy.
        echo json_encode([
            'success' => true,
            'notifications' => [],
            'warning' => 'Dispatch history is temporarily unavailable due to database table health issues.'
        ]);
    }
} elseif ($action === 'get_options') {
    try {
        // Fetch Barangays (graceful fallback when users table is unavailable/corrupted)
        $barangays = [];
        $optionWarnings = [];
        try {
            $bStmt = $pdo->query("SELECT DISTINCT barangay FROM users WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay");
            $barangays = $bStmt ? $bStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        } catch (Throwable $e) {
            $barangays = [];
            $optionWarnings[] = 'Barangay list is temporarily unavailable.';
            error_log("Mass Notification get_options barangay query error: " . $e->getMessage());
        }

        // Fetch Categories with auto-heal schema/seed
        $categories = [];
        $categoriesTable = null;
        try {
            $catResult = mnGetCategoriesForOptions($pdo);
            $categories = $catResult['categories'] ?? [];
            $categoriesTable = $catResult['table'] ?? null;
        } catch (Throwable $e) {
            error_log("Mass Notification get_options categories error: " . $e->getMessage());
            $categories = [];
            $categoriesTable = null;
        }

        // Fetch Templates (optional table)
        $templates = [];
        $hasTemplates = false;
        try {
            $tCheck = $pdo->query("SHOW TABLES LIKE 'notification_templates'");
            $hasTemplates = $tCheck && $tCheck->rowCount() > 0;
        } catch (PDOException $e) {
            $hasTemplates = false;
        }

        if ($hasTemplates) {
            // Category join is optional and table-name aware.
            if (!empty($categoriesTable)) {
                $tStmt = $pdo->query("SELECT t.*, c.name as category_name FROM notification_templates t LEFT JOIN {$categoriesTable} c ON t.category_id = c.id ORDER BY t.created_at DESC");
            } else {
                $tStmt = $pdo->query("SELECT t.* FROM notification_templates t ORDER BY t.created_at DESC");
            }
            $templates = $tStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'barangays' => $barangays,
            'categories' => $categories,
            'templates' => $templates,
            'warnings' => $optionWarnings
        ]);
    } catch (PDOException $e) {
        error_log("Mass Notification get_options fatal error: " . $e->getMessage());
        // Degraded mode: return minimal payload to avoid frontend hard-failure.
        echo json_encode([
            'success' => true,
            'barangays' => [],
            'categories' => [],
            'templates' => [],
            'warnings' => ['Options are temporarily unavailable due to database table health issues.']
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>

