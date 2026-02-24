<?php
/**
 * Global Alert Polling Endpoint
 * Returns the latest unread alert for the current user
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';

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

// Current user identification (assuming citizen user_id or admin_user_id)
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $alertsTable = resolveRealtimeAlertsTable($pdo);
    $logsTable = resolveRealtimeLogsTable($pdo);
    $hasCategoryTable = realtimeHasReadableTable($pdo, 'alert_categories');

    // 1. Get user's subscribed categories
    $stmt = $pdo->prepare("SELECT category_id FROM user_subscriptions WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get user's barangay/role for targeting
    $uStmt = $pdo->prepare("SELECT barangay, user_type FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    $barangay = $user['barangay'] ?? '';
    $userType = $user['user_type'] ?? '';

    // 3. Find the latest alert that the user hasn't seen yet
    // Filter by: Subscription OR Targeted Barangay OR Targeted Role
    $sql = "SELECT a.*";
    if ($hasCategoryTable) {
        $sql .= ", c.name as category_name, c.icon as category_icon, c.color as category_color
            FROM {$alertsTable} a
            LEFT JOIN alert_categories c ON a.category_id = c.id
            WHERE a.status = 'active'";
    } else {
        $sql .= ", COALESCE(a.category, 'General') as category_name, 'fa-exclamation-triangle' as category_icon, '#95a5a6' as category_color
            FROM {$alertsTable} a
            WHERE a.status = 'active'
        ";
    }
    $sql .= "
            AND a.id NOT IN (SELECT alert_id FROM user_alert_marks WHERE user_id = ?)
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

    // 4. Get unread count for badge
    $cStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM {$alertsTable} a
        WHERE a.status = 'active'
        AND a.id NOT IN (SELECT alert_id FROM user_alert_marks WHERE user_id = ?)
        AND (
            a.category_id IN (" . (empty($subscriptions) ? "0" : implode(',', array_map('intval', $subscriptions))) . ")
            OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
            OR a.id IN (SELECT log_id FROM {$logsTable} WHERE recipients LIKE ?)
        )
    ");
    $cStmt->execute([$userId, "%$barangay%", "%$userType%"]);
    $unreadCount = $cStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'alert' => $latestAlert,
        'unread_count' => (int)$unreadCount,
        'server_time' => date('Y-m-d H:i:s'),
        'language' => $targetLanguage ?? 'en'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
