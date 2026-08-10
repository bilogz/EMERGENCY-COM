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
                alert_id BIGINT UNSIGNED NULL,
                category_id INT UNSIGNED NULL,
                category_name VARCHAR(120) NULL,
                sent_by_name VARCHAR(160) NULL,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                response LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status_sent_at (status, sent_at),
                INDEX idx_channel (channel)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $colsStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
        $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $optionalColumns = [
            'alert_id' => "ALTER TABLE {$tableName} ADD COLUMN alert_id BIGINT UNSIGNED NULL AFTER ip_address",
            'category_id' => "ALTER TABLE {$tableName} ADD COLUMN category_id INT UNSIGNED NULL AFTER alert_id",
            'category_name' => "ALTER TABLE {$tableName} ADD COLUMN category_name VARCHAR(120) NULL AFTER category_id",
            'sent_by_name' => "ALTER TABLE {$tableName} ADD COLUMN sent_by_name VARCHAR(160) NULL AFTER sent_by",
            'is_deleted' => "ALTER TABLE {$tableName} ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER response",
            'deleted_at' => "ALTER TABLE {$tableName} ADD COLUMN deleted_at DATETIME NULL AFTER is_deleted"
        ];
        foreach ($optionalColumns as $column => $sql) {
            if (!in_array($column, $cols, true)) {
                try { $pdo->exec($sql); } catch (Throwable $e) {}
            }
        }
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
}function mnResolveAdminDisplayName(PDO $pdo, string $sentBy): string {
    $sentBy = trim($sentBy);
    if ($sentBy === '') return 'System';
    if (!preg_match('/admin_(\d+)/', $sentBy, $m)) return ucwords(str_replace('_', ' ', $sentBy));
    $adminId = (int)$m[1];
    foreach (['admins', 'admin_users', 'users'] as $table) {
        try {
            $colsStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!in_array('id', $cols, true)) continue;
            $nameCols = array_values(array_filter(['name', 'full_name', 'username', 'email'], fn($c) => in_array($c, $cols, true)));
            if (!$nameCols) continue;
            $select = implode(', ', array_map(fn($c) => "`{$c}`", $nameCols));
            $stmt = $pdo->prepare("SELECT {$select} FROM `{$table}` WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($row as $value) {
                    $value = trim((string)$value);
                    if ($value !== '') return $value;
                }
            }
        } catch (Throwable $e) {}
    }
    return 'Admin #' . $adminId;
}

function mnCategoryNameById(PDO $pdo, $categoryId): string {
    $id = (int)$categoryId;
    if ($id <= 0) return 'General';
    foreach (['alert_categories', 'alert_categories_catalog'] as $table) {
        try {
            $colsStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!in_array('id', $cols, true) || !in_array('name', $cols, true)) continue;
            $stmt = $pdo->prepare("SELECT name FROM `{$table}` WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $name = trim((string)$stmt->fetchColumn());
            if ($name !== '') return $name;
        } catch (Throwable $e) {}
    }
    return 'General';
}

function mnEnsureNotificationTemplatesTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notification_templates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NULL,
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            what_happened TEXT NULL,
            where_happening TEXT NULL,
            action_to_take TEXT NULL,
            weather_signal TINYINT UNSIGNED NULL,
            fire_level TINYINT UNSIGNED NULL,
            severity VARCHAR(20) NOT NULL DEFAULT 'Medium',
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    try {
        $colsStmt = $pdo->query("SHOW COLUMNS FROM notification_templates");
        $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $optionalColumns = [
            'what_happened' => "ALTER TABLE notification_templates ADD COLUMN what_happened TEXT NULL AFTER body",
            'where_happening' => "ALTER TABLE notification_templates ADD COLUMN where_happening TEXT NULL AFTER what_happened",
            'action_to_take' => "ALTER TABLE notification_templates ADD COLUMN action_to_take TEXT NULL AFTER where_happening",
            'weather_signal' => "ALTER TABLE notification_templates ADD COLUMN weather_signal TINYINT UNSIGNED NULL AFTER action_to_take",
            'fire_level' => "ALTER TABLE notification_templates ADD COLUMN fire_level TINYINT UNSIGNED NULL AFTER weather_signal"
        ];
        foreach ($optionalColumns as $column => $sql) {
            if (!in_array($column, $cols, true)) {
                try { $pdo->exec($sql); } catch (Throwable $e) {}
            }
        }
    } catch (Throwable $e) {}
}


function mnEnsureAlertRecipientsTable(PDO $pdo): void {
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

}

function mnPersistAlertRecipients(PDO $pdo, int $alertId, array $subscribers): int {
    if ($alertId <= 0 || empty($subscribers)) {
        return 0;
    }
    try {
        mnEnsureAlertRecipientsTable($pdo);

        $stmt = $pdo->prepare("INSERT IGNORE INTO alert_recipients (alert_id, user_id) VALUES (?, ?)");
        $inserted = 0;
        $seen = [];
        foreach ($subscribers as $subscriber) {
            $userId = (int)($subscriber['user_id'] ?? 0);
            if ($userId <= 0 || isset($seen[$userId])) {
                continue;
            }
            $seen[$userId] = true;
            $stmt->execute([$alertId, $userId]);
            $inserted++;
        }
        return $inserted;
    } catch (Throwable $e) {
        error_log('mnPersistAlertRecipients degraded mode: ' . $e->getMessage());
        return 0;
    }
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
        require_once __DIR__ . '/../app/repositories/AlertRepository.php';
        $alertRepository = new AlertRepository($pdo);
        
        $categoryId = null;
        if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
            $categoryId = intval($_POST['category_id']);
        } else {
            // Try to find or create a "General" category
            $categoryId = $alertRepository->findOrGetDefaultCategoryId('General');
        }

        $categoryKind = 'general';
        if ($categoryId) {
            foreach (['alert_categories', 'alert_categories_catalog'] as $catTable) {
                try {
                    $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($catTable));
                    if (!$exists || !$exists->fetch()) {
                        continue;
                    }
                    $cStmt = $pdo->prepare("SELECT name FROM {$catTable} WHERE id = ? LIMIT 1");
                    $cStmt->execute([$categoryId]);
                    $catName = strtolower((string)($cStmt->fetchColumn() ?: ''));
                    if ($catName !== '') {
                        if (strpos($catName, 'fire') !== false || strpos($catName, 'smoke') !== false || strpos($catName, 'burn') !== false) {
                            $categoryKind = 'fire';
                        } elseif (strpos($catName, 'weather') !== false || strpos($catName, 'storm') !== false || strpos($catName, 'typhoon') !== false) {
                            $categoryKind = 'weather';
                        } elseif (strpos($catName, 'earthquake') !== false || strpos($catName, 'seismic') !== false) {
                            $categoryKind = 'earthquake';
                        }
                        break;
                    }
                } catch (Throwable $e) {
                    // Continue with fallback kind.
                }
            }
        }

        $severityNorm = strtolower(trim((string)$severity));
        if (!in_array($severityNorm, ['low', 'medium', 'high', 'critical'], true)) {
            $severityNorm = 'medium';
        }
        
        $weatherSignal = null;
        if ($weatherSignalRaw !== null && $weatherSignalRaw !== '') {
            $weatherSignal = (int)$weatherSignalRaw;
            if ($weatherSignal < 1 || $weatherSignal > 5) $weatherSignal = null;
        }

        $fireLevel = null;
        if ($fireLevelRaw !== null && $fireLevelRaw !== '') {
            $fireLevel = (int)$fireLevelRaw;
            if ($fireLevel < 1 || $fireLevel > 5) $fireLevel = null;
        }

        if ($categoryKind === 'fire' || $fireLevel !== null) {
            if ($fireLevel === null || $fireLevel < 1 || $fireLevel > 5) {
                $fireLevel = 5;
            }
            if ($fireLevel >= 4) {
                $severityNorm = 'critical';
            } elseif ($severityNorm !== 'high' && $severityNorm !== 'critical') {
                $severityNorm = 'high';
            }
        }
        $severity = $severityNorm;

        // Insert alert into alerts table and mark source as mass_notification
        $alertSource = 'mass_notification';
        $alertId = $alertRepository->create($title, $message, $message, $categoryId, 'active', $severity, $weatherSignal, $fireLevel, $alertSource);
        
        // Initialize translation helper
        $translationHelper = new AlertTranslationHelper($pdo);
        
        // Get all subscribers based on recipient selection (using repository)
        require_once __DIR__ . '/../app/repositories/SubscriberRepository.php';
        $subscriberRepository = new SubscriberRepository($pdo);
        
        $subscribers = [];
        if (is_array($recipients)) {
            $subscribers = $subscriberRepository->getByRecipients($recipients);
        }
        $targetedRecipientCount = mnPersistAlertRecipients($pdo, (int)$alertId, $subscribers);

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
                $emailStatus = 'sent';
                $mailLibPath = dirname(__DIR__, 2) . '/USERS/lib/mail.php';
                if (file_exists($mailLibPath)) {
                    require_once $mailLibPath;
                    $mailErr = null;
                    $emailSubject = $translatedAlert['title'] ?? 'Emergency Alert';
                    $emailBody = $translatedAlert['message'] ?? $translatedMessage;
                    $mailSent = sendSMTPMail($subscriber['email'], $emailSubject, $emailBody, false, $mailErr);
                    if (!$mailSent) {
                        $emailStatus = 'failed';
                        error_log("Mass notification direct email error for {$subscriber['email']}: " . ($mailErr ?? 'unknown'));
                    }
                }

                // Log email notification
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address, alert_id, user_language)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['email'], 
                        $recipientsStr, 
                        $priority, 
                        $emailStatus,
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress,
                        $alertId,
                        $userLanguage
                    ]);
                } catch (PDOException $e) {
                    // Fallback if alert_id/user_language columns don't exist
                    $stmt = $pdo->prepare("
                        INSERT INTO notification_logs (channel, message, recipient, recipients, priority, status, sent_at, sent_by, ip_address)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                    ");
                    $stmt->execute([
                        $channel, 
                        $translatedMessage, 
                        $subscriber['email'], 
                        $recipientsStr, 
                        $priority, 
                        $emailStatus,
                        $adminId ? 'admin_' . $adminId : 'system',
                        $ipAddress
                    ]);
                }
                $sentCount++;
                
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
            'targeted_recipients' => $targetedRecipientCount,
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
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(10, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        $fetchLimit = min(250, $offset + $limit + 1);
        $sourceSaturated = false;

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
        $selectParts[] = in_array('sent_by', $cols, true) ? 'sent_by' : "'' as sent_by";
        $selectParts[] = in_array('sent_by_name', $cols, true) ? 'sent_by_name' : "'' as sent_by_name";
        $selectParts[] = in_array('alert_id', $cols, true) ? 'alert_id' : "NULL as alert_id";
        $selectParts[] = in_array('category_id', $cols, true) ? 'category_id' : "NULL as category_id";
        $selectParts[] = in_array('category_name', $cols, true) ? 'category_name' : "'' as category_name";

        $hasSentAt = in_array('sent_at', $cols, true);
        $hasCreatedAt = in_array('created_at', $cols, true);
        $hasResponse = in_array('response', $cols, true);

        if ($hasSentAt) $selectParts[] = 'sent_at';
        elseif ($hasCreatedAt) $selectParts[] = 'created_at as sent_at';
        else $selectParts[] = "NULL as sent_at";

        if ($hasCreatedAt) $selectParts[] = 'created_at as sort_created_at';
        elseif ($hasSentAt) $selectParts[] = 'sent_at as sort_created_at';
        else $selectParts[] = "NULL as sort_created_at";

        if ($hasResponse) $selectParts[] = 'response';
        else $selectParts[] = "NULL as response";

        if ($hasSentAt && $hasCreatedAt) {
            $orderBy = 'COALESCE(sent_at, created_at)';
        } elseif ($hasSentAt) {
            $orderBy = 'sent_at';
        } elseif ($hasCreatedAt) {
            $orderBy = 'created_at';
        } else {
            $orderBy = 'id';
        }

        $whereDeleted = in_array('is_deleted', $cols, true) ? 'WHERE is_deleted = 0' : '';
        $totalStmt = $pdo->query("SELECT COUNT(*) FROM {$logsTable} {$whereDeleted}");
        $totalRows = $totalStmt ? (int)$totalStmt->fetchColumn() : 0;
        $stmt = $pdo->query("
            SELECT " . implode(', ', $selectParts) . "
            FROM {$logsTable}
            {$whereDeleted}
            ORDER BY $orderBy DESC, id DESC
            LIMIT {$fetchLimit}
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sourceSaturated = count($notifications) >= $fetchLimit;

        // Older PAGASA/PHIVOLCS jobs used source-specific history tables. Add
        // those legacy rows so operators have one complete dispatch history.
        // New bulletin jobs already write to notification_logs and are excluded
        // here to prevent duplicate rows.
        $legacyAutomatic = [];
        try {
            if ($pdo->query("SHOW TABLES LIKE 'pagasa_auto_alert_log'")->fetchColumn()) {
                $autoStmt = $pdo->query("
                    SELECT p.* FROM pagasa_auto_alert_log p
                    WHERE p.dispatch_log_id IS NULL OR NOT EXISTS (
                        SELECT 1 FROM {$logsTable} nl
                        WHERE nl.id = p.dispatch_log_id AND nl.sent_by = 'pagasa_auto_bulletin'
                    )
                    ORDER BY p.created_at DESC LIMIT {$fetchLimit}
                ");
                $legacyRows = $autoStmt->fetchAll(PDO::FETCH_ASSOC);
                $sourceSaturated = $sourceSaturated || count($legacyRows) >= $fetchLimit;
                foreach ($legacyRows as $row) {
                    $total = (int)($row['recipients_count'] ?? 0);
                    $legacyAutomatic[] = [
                        'id' => 'PAGASA-' . $row['id'],
                        'channel' => $row['channels'] ?? 'push,email',
                        'message' => trim(($row['bulletin_title'] ?? 'PAGASA Bulletin') . ' — ' . ($row['bulletin_summary'] ?? '')),
                        'recipients' => 'All Citizens · PAGASA Auto',
                        'sent_by' => 'pagasa_auto_bulletin',
                        'sent_by_name' => 'PAGASA Auto Bulletin',
                        'category_name' => 'Weather',
                        'status' => 'completed',
                        'sent_at' => $row['created_at'] ?? null,
                        'sort_created_at' => $row['created_at'] ?? null,
                        'response' => json_encode(['sent' => $total, 'failed' => 0, 'total' => $total, 'progress' => 100]),
                    ];
                }
            }
        } catch (Throwable $legacyPagasaError) {
            error_log('PAGASA legacy history unavailable: ' . $legacyPagasaError->getMessage());
        }
        try {
            if ($pdo->query("SHOW TABLES LIKE 'phivolcs_auto_alert_log'")->fetchColumn()) {
                $autoStmt = $pdo->query("
                    SELECT p.* FROM phivolcs_auto_alert_log p
                    WHERE p.dispatch_log_id IS NULL OR NOT EXISTS (
                        SELECT 1 FROM {$logsTable} nl
                        WHERE nl.id = p.dispatch_log_id AND nl.sent_by = 'phivolcs_auto_bulletin'
                    )
                    ORDER BY p.created_at DESC LIMIT {$fetchLimit}
                ");
                $legacyRows = $autoStmt->fetchAll(PDO::FETCH_ASSOC);
                $sourceSaturated = $sourceSaturated || count($legacyRows) >= $fetchLimit;
                foreach ($legacyRows as $row) {
                    $total = (int)($row['recipients_count'] ?? 0);
                    $legacyAutomatic[] = [
                        'id' => 'PHIVOLCS-' . $row['id'],
                        'channel' => 'push,email',
                        'message' => sprintf('PHIVOLCS Earthquake Bulletin — M%.1f · %s', (float)($row['magnitude'] ?? 0), $row['location'] ?? 'Philippines'),
                        'recipients' => 'All Citizens · PHIVOLCS Auto',
                        'sent_by' => 'phivolcs_auto_bulletin',
                        'sent_by_name' => 'PHIVOLCS Auto Bulletin',
                        'category_name' => 'Earthquake',
                        'status' => 'completed',
                        'sent_at' => $row['created_at'] ?? null,
                        'sort_created_at' => $row['created_at'] ?? null,
                        'response' => json_encode(['sent' => $total, 'failed' => 0, 'total' => $total, 'progress' => 100]),
                    ];
                }
            }
        } catch (Throwable $legacyPhivolcsError) {
            error_log('PHIVOLCS legacy history unavailable: ' . $legacyPhivolcsError->getMessage());
        }
        if ($legacyAutomatic) {
            $notifications = array_merge($notifications, $legacyAutomatic);
        }
        usort($notifications, static function (array $a, array $b): int {
            $aTime = (string)($a['sent_at'] ?? $a['sort_created_at'] ?? '');
            $bTime = (string)($b['sent_at'] ?? $b['sort_created_at'] ?? '');
            $timeCompare = strcmp($bTime, $aTime);
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            $aId = (int)preg_replace('/\D+/', '', (string)($a['id'] ?? '0'));
            $bId = (int)preg_replace('/\D+/', '', (string)($b['id'] ?? '0'));
            return $bId <=> $aId;
        });

        $combinedCount = count($notifications);
        $hasMore = $sourceSaturated || $combinedCount > ($offset + $limit);
        $notifications = array_slice($notifications, $offset, $limit);

        // Optional: compute queue stats when response column is missing/empty
        $queueStatsByLog = [];
        $logIds = array_values(array_filter(
            array_map(fn($n) => $n['id'] ?? null, $notifications),
            static fn($id) => is_int($id) || (is_string($id) && ctype_digit($id))
        ));
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
        
        // Ensure status, progress, category, and admin labels are calculated correctly for UI.
        foreach ($notifications as &$notif) {
            $notif['sent_by_name'] = trim((string)($notif['sent_by_name'] ?? '')) !== ''
                ? trim((string)$notif['sent_by_name'])
                : mnResolveAdminDisplayName($pdo, (string)($notif['sent_by'] ?? ''));
            if (trim((string)($notif['category_name'] ?? '')) === '') {
                $categoryId = $notif['category_id'] ?? null;
                if (!$categoryId && preg_match('/Cat\s+(\d+)/i', (string)($notif['recipients'] ?? ''), $m)) {
                    $categoryId = (int)$m[1];
                    $notif['category_id'] = $categoryId;
                }
                $notif['category_name'] = $categoryId ? mnCategoryNameById($pdo, $categoryId) : 'General';
            }
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
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => $hasMore,
                'total' => $totalRows ?? count($notifications),
                'total_pages' => max(1, (int)ceil((($totalRows ?? count($notifications)) ?: 0) / max(1, $limit)))
            ],
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
} elseif ($action === 'save_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        mnEnsureNotificationTemplatesTable($pdo);
        $adminId = (int)($_SESSION['admin_user_id'] ?? 0);
        $categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $whatHappened = trim((string)($_POST['what_happened'] ?? ''));
        $whereHappening = trim((string)($_POST['where_happening'] ?? ''));
        $actionToTake = trim((string)($_POST['action_to_take'] ?? ''));
        $weatherSignal = isset($_POST['weather_signal']) && preg_match('/^[1-5]$/', (string)$_POST['weather_signal']) ? (int)$_POST['weather_signal'] : null;
        $fireLevel = isset($_POST['fire_level']) && preg_match('/^[1-5]$/', (string)$_POST['fire_level']) ? (int)$_POST['fire_level'] : null;
        $severity = trim((string)($_POST['severity'] ?? 'Medium'));
        if (!in_array($severity, ['Low', 'Medium', 'High', 'Critical'], true)) $severity = 'Medium';
        if ($title === '' || $body === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Template title and message are required.']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO notification_templates (category_id, title, body, what_happened, where_happening, action_to_take, weather_signal, fire_level, severity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$categoryId, $title, $body, $whatHappened ?: null, $whereHappening ?: null, $actionToTake ?: null, $weatherSignal, $fireLevel, $severity, $adminId ?: null]);
        echo json_encode(['success' => true, 'template_id' => (int)$pdo->lastInsertId()]);
    } catch (Throwable $e) {
        http_response_code(500);
        error_log('Mass Notification save_template error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to save template.']);
    }
} elseif ($action === 'delete_log' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = trim((string)($_POST['id'] ?? ''));
        if ($id === '' || !ctype_digit($id)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Valid dispatch ID is required.']);
            exit;
        }
        $logsTable = mnResolveNotificationLogsTable($pdo);
        mnEnsureNotificationLogsTable($pdo, $logsTable);
        $stmt = $pdo->prepare("UPDATE {$logsTable} SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$id]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        error_log('Mass Notification delete_log error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to delete dispatch record.']);
    }} elseif ($action === 'get_options') {
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

        mnEnsureNotificationTemplatesTable($pdo);

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


