<?php
/**
 * Global Alert Polling Endpoint
 * Returns the latest unread alert for the current user
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../ADMIN/api/db_connect.php';

session_start();

function normalizeRealtimeAlertLanguage($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    if ($lang !== '' && !preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
        return '';
    }
    return $lang;
}

function resolveRealtimeUserLanguage(PDO $pdo, int $userId): string {
    if ($userId <= 0) {
        return 'en';
    }

    $queries = [
        [
            "SELECT preferred_language FROM user_preferences
             WHERE user_id = ? AND preferred_language IS NOT NULL AND preferred_language <> ''
             ORDER BY id DESC LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language FROM subscriptions
             WHERE user_id = ? AND status = 'active' AND preferred_language IS NOT NULL AND preferred_language <> ''
             ORDER BY id DESC LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language FROM users
             WHERE id = ? AND preferred_language IS NOT NULL AND preferred_language <> ''
             LIMIT 1",
            [$userId]
        ],
    ];

    foreach ($queries as [$sql, $params]) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['preferred_language'])) {
                $lang = normalizeRealtimeAlertLanguage($row['preferred_language']);
                if ($lang !== '') {
                    return $lang;
                }
            }
        } catch (Throwable $e) {
            // Continue to next source.
        }
    }

    return 'en';
}

function realtimeHasReadableTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }
    try {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
        if (!$exists || !$exists->fetch()) {
            return false;
        }
        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function realtimeEnsureAlertsRuntimeTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT(11) NOT NULL AUTO_INCREMENT,
            category_id INT(11) DEFAULT NULL,
            incident_id INT(11) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            content TEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            area VARCHAR(255) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            latitude DECIMAL(10,8) DEFAULT NULL,
            longitude DECIMAL(11,8) DEFAULT NULL,
            source VARCHAR(100) DEFAULT NULL,
            severity VARCHAR(20) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    try {
        $pdo->exec($sql);
        $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
        return true;
    } catch (Throwable $e) {
        $msg = strtolower($e->getMessage());
        $isTablespaceIssue = (strpos($msg, '1813') !== false)
            || (strpos($msg, '1932') !== false)
            || (strpos($msg, "doesn't exist in engine") !== false)
            || (strpos($msg, 'tablespace for table') !== false);
        if ($isTablespaceIssue) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS {$tableName}");
                $pdo->exec($sql);
                $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
                return true;
            } catch (Throwable $rebuildEx) {
                return false;
            }
        }
        return false;
    }
}

function resolveRealtimeAlertsTable(PDO $pdo): string {
    if (realtimeHasReadableTable($pdo, 'alerts')) {
        return 'alerts';
    }

    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (realtimeHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }
    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (realtimeEnsureAlertsRuntimeTable($pdo, $candidate) && realtimeHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }

    return 'alerts';
}

function resolveRealtimeLogsTable(PDO $pdo): string {
    if (realtimeHasReadableTable($pdo, 'notification_logs')) {
        return 'notification_logs';
    }
    return realtimeHasReadableTable($pdo, 'notification_logs_runtime') ? 'notification_logs_runtime' : 'notification_logs';
}

function ensureRealtimeAlertMarks(PDO $pdo): string {
    foreach (['user_alert_marks', 'user_alert_marks_runtime'] as $candidate) {
        if (realtimeHasReadableTable($pdo, $candidate)) return $candidate;
    }
    $table = 'user_alert_marks_runtime';
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        alert_id BIGINT UNSIGNED NOT NULL,
        acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_alert (user_id, alert_id),
        INDEX idx_alert_id (alert_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    return $table;
}

// Pop-up alerts are citizen-only. Admin sessions use the admin notification UI.
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $marksTable = ensureRealtimeAlertMarks($pdo);
    $alertsTable = resolveRealtimeAlertsTable($pdo);
    $logsTable = resolveRealtimeLogsTable($pdo);
    $categoriesTable = realtimeHasReadableTable($pdo, 'alert_categories')
        ? 'alert_categories'
        : (realtimeHasReadableTable($pdo, 'alert_categories_catalog') ? 'alert_categories_catalog' : null);
    $hasCategoryTable = $categoriesTable !== null;
    $hasRecipientsMap = realtimeHasReadableTable($pdo, 'alert_recipients');
    $logColumns = realtimeHasReadableTable($pdo, $logsTable)
        ? $pdo->query("SHOW COLUMNS FROM {$logsTable}")->fetchAll(PDO::FETCH_COLUMN)
        : [];
    $hasHistoryClock = in_array('message', $logColumns, true) && in_array('sent_at', $logColumns, true);
    // Compatibility for alerts created before the timezone bug was fixed:
    // notification_logs.sent_at used MySQL's correct local clock, so use the
    // matching dispatch time when the alert row itself was saved hours behind.
    $historyIssuedAt = $hasHistoryClock
        ? "(SELECT MAX(nl.sent_at) FROM {$logsTable} nl WHERE nl.message = COALESCE(a.message, a.content, ''))"
        : 'NULL';
    $freshCondition = "(a.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)"
        . ($hasHistoryClock ? " OR {$historyIssuedAt} >= DATE_SUB(NOW(), INTERVAL 6 HOUR)" : '')
        . ')';

    $latestAlert = null;
    $unreadCount = 0;

    if ($hasRecipientsMap) {
        // Recipient-mapped visibility:
        // - global alerts (no map rows) are visible to all
        // - targeted alerts are visible only to mapped users
        $sql = "SELECT a.*, COALESCE({$historyIssuedAt}, a.created_at) AS issued_at";
        if ($hasCategoryTable) {
            $sql .= ", c.name as category_name, c.icon as category_icon, c.color as category_color
                FROM {$alertsTable} a
                LEFT JOIN {$categoriesTable} c ON a.category_id = c.id
                WHERE a.status = 'active'
                AND {$freshCondition}";
        } else {
            $sql .= ", COALESCE(a.category, 'General') as category_name, 'fa-exclamation-triangle' as category_icon, '#95a5a6' as category_color
                FROM {$alertsTable} a
                WHERE a.status = 'active'
                AND {$freshCondition}";
        }
        $sql .= "
            AND a.id NOT IN (SELECT alert_id FROM {$marksTable} WHERE user_id = ?)
            AND (
                NOT EXISTS (SELECT 1 FROM alert_recipients ar0 WHERE ar0.alert_id = a.id)
                OR EXISTS (SELECT 1 FROM alert_recipients ar WHERE ar.alert_id = a.id AND ar.user_id = ?)
            )
            ORDER BY a.created_at DESC
            LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $latestAlert = $stmt->fetch(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM {$alertsTable} a
            WHERE a.status = 'active'
            AND {$freshCondition}
            AND a.id NOT IN (SELECT alert_id FROM {$marksTable} WHERE user_id = ?)
            AND (
                NOT EXISTS (SELECT 1 FROM alert_recipients ar0 WHERE ar0.alert_id = a.id)
                OR EXISTS (SELECT 1 FROM alert_recipients ar WHERE ar.alert_id = a.id AND ar.user_id = ?)
            )
        ");
        $cStmt->execute([$userId, $userId]);
        $unreadCount = (int)$cStmt->fetchColumn();
    } else {
        // Backward-compatible fallback for deployments without recipient map.
        $stmt = $pdo->prepare("SELECT category_id FROM user_subscriptions WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $uStmt = $pdo->prepare("SELECT barangay, user_type FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC);
        $barangay = $user['barangay'] ?? '';
        $userType = $user['user_type'] ?? '';

        $sql = "SELECT a.*, COALESCE({$historyIssuedAt}, a.created_at) AS issued_at";
        if ($hasCategoryTable) {
            $sql .= ", c.name as category_name, c.icon as category_icon, c.color as category_color
                FROM {$alertsTable} a
                LEFT JOIN {$categoriesTable} c ON a.category_id = c.id
                WHERE a.status = 'active'
                AND {$freshCondition}";
        } else {
            $sql .= ", COALESCE(a.category, 'General') as category_name, 'fa-exclamation-triangle' as category_icon, '#95a5a6' as category_color
                FROM {$alertsTable} a
                WHERE a.status = 'active'
                AND {$freshCondition}
            ";
        }
        $sql .= "
                AND a.id NOT IN (SELECT alert_id FROM {$marksTable} WHERE user_id = ?)
                AND (
                    a.category_id IN (" . (empty($subscriptions) ? "0" : implode(',', array_map('intval', $subscriptions))) . ")
                    OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
                    OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
                )
                ORDER BY a.created_at DESC
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            "%$barangay%",
            "%$userType%"
        ]);
        $latestAlert = $stmt->fetch(PDO::FETCH_ASSOC);

        $cStmt = $pdo->prepare("
            SELECT COUNT(*) 
                FROM {$alertsTable} a
                WHERE a.status = 'active'
                AND {$freshCondition}
                AND a.id NOT IN (SELECT alert_id FROM {$marksTable} WHERE user_id = ?)
            AND (
                a.category_id IN (" . (empty($subscriptions) ? "0" : implode(',', array_map('intval', $subscriptions))) . ")
                OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
                OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
            )
        ");
        $cStmt->execute([$userId, "%$barangay%", "%$userType%"]);
        $unreadCount = (int)$cStmt->fetchColumn();
    }

    // Translate latest alert according to user preference/device-derived preference
    $targetLanguage = resolveRealtimeUserLanguage($pdo, (int)$userId);
    if ($latestAlert && $targetLanguage !== 'en' && file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
        require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
        if (class_exists('AlertTranslationHelper')) {
            $translationHelper = new AlertTranslationHelper($pdo);
            $sourceTitle = (string)($latestAlert['title'] ?? '');
            $sourceMessage = (string)($latestAlert['message'] ?? ($latestAlert['content'] ?? ''));
            $translated = $translationHelper->getTranslatedAlert((int)$latestAlert['id'], $targetLanguage, $sourceTitle, $sourceMessage);
            if ($translated && !empty($translated['title']) && !empty($translated['message'])) {
                $latestAlert['title'] = $translated['title'];
                $latestAlert['message'] = $translated['message'];
                $latestAlert['content'] = $translated['message'];
            }
        }
    }

    if ($latestAlert) {
        $severity = strtolower(trim((string)($latestAlert['severity'] ?? 'medium')));
        $latestAlert['severity'] = in_array($severity, ['low', 'medium', 'high', 'critical'], true)
            ? ucfirst($severity)
            : 'Medium';
        $latestAlert['message'] = (string)($latestAlert['message'] ?? ($latestAlert['content'] ?? ''));
        $latestAlert['category_name'] = (string)($latestAlert['category_name'] ?? ($latestAlert['category'] ?? 'Emergency Alert'));
        $latestAlert['category_icon'] = (string)($latestAlert['category_icon'] ?? 'fa-triangle-exclamation');
    }

    echo json_encode([
        'success' => true,
        'alert' => $latestAlert,
        'unread_count' => (int)$unreadCount,
        'server_time' => date('Y-m-d H:i:s'),
        'freshness_hours' => 6,
        'language' => $targetLanguage ?? 'en'
    ]);

} catch (Throwable $e) {
    error_log('Realtime alert polling error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Alert service is temporarily unavailable.']);
}
