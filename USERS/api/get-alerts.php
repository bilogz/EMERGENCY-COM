<?php
/**
 * Get Alerts API
 * Returns alerts and auto-translates per user preference or device language.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Normalize language code and map aliases.
 */
function normalizeAlertLanguageCode($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    if ($lang !== '' && !preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
        return '';
    }
    return $lang;
}

/**
 * Resolve language from browser/device headers.
 */
function detectBrowserLanguage(): string {
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (empty($acceptLanguage)) {
        return 'en';
    }
    $first = trim(explode(',', $acceptLanguage)[0] ?? '');
    if ($first === '') {
        return 'en';
    }
    $base = strtolower(trim(explode('-', $first)[0] ?? ''));
    $normalized = normalizeAlertLanguageCode($base);
    return $normalized !== '' ? $normalized : 'en';
}

/**
 * Resolve logged-in user's saved language preference.
 */
function getUserPreferredLanguage(PDO $pdo, int $userId): string {
    if ($userId <= 0) {
        return '';
    }

    $queries = [
        [
            "SELECT preferred_language
             FROM user_preferences
             WHERE user_id = ?
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language
             FROM subscriptions
             WHERE user_id = ?
               AND status = 'active'
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        ],
        [
            "SELECT preferred_language
             FROM users
             WHERE id = ?
               AND preferred_language IS NOT NULL
               AND preferred_language <> ''
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
                $lang = normalizeAlertLanguageCode($row['preferred_language']);
                if ($lang !== '') {
                    return $lang;
                }
            }
        } catch (Throwable $e) {
            // Backward-compatible: ignore missing tables/columns and continue.
        }
    }

    return '';
}

/**
 * Resolve target language:
 * 1) Query parameter from UI selector
 * 2) Logged-in user's DB preference
 * 3) Browser/device language
 * 4) English fallback
 */
function resolveTargetLanguage(PDO $pdo): string {
    $requestedLang = normalizeAlertLanguageCode($_GET['lang'] ?? '');
    if ($requestedLang !== '') {
        return $requestedLang;
    }

    $sessionUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($sessionUserId > 0) {
        $pref = getUserPreferredLanguage($pdo, $sessionUserId);
        if ($pref !== '') {
            return $pref;
        }
    }

    $browserLang = detectBrowserLanguage();
    if ($browserLang !== '') {
        return $browserLang;
    }

    return 'en';
}

/**
 * Calculate time ago string.
 */
function getTimeAgo($datetime): string {
    if (empty($datetime)) {
        return 'Recently';
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'Recently';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    return date('M d, Y', $timestamp);
}

function usersHasReadableTable(PDO $pdo, string $tableName): bool {
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

function usersEnsureRuntimeAlertsTable(PDO $pdo, string $tableName): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }

    $runtimeSql = "
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
        $pdo->exec($runtimeSql);
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
                $pdo->exec($runtimeSql);
                $pdo->query("SELECT 1 FROM {$tableName} LIMIT 1");
                return true;
            } catch (Throwable $rebuildEx) {
                return false;
            }
        }
        return false;
    }
}

function usersResolveAlertsTable(PDO $pdo): string {
    if (usersHasReadableTable($pdo, 'alerts')) {
        return 'alerts';
    }

    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (usersHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }

    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (usersEnsureRuntimeAlertsTable($pdo, $candidate) && usersHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }

    return 'alerts';
}

function usersTableHasColumn(PDO $pdo, string $tableName, string $column): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        return false;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tableName} LIKE " . $pdo->quote($column));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Filter alerts so targeted alerts are only visible to mapped recipients.
 * Legacy/global alerts (without alert_recipients rows) remain visible.
 */
function usersFilterAlertsByRecipientMap(PDO $pdo, array $alerts, int $userId, bool $bypassFilter = false): array {
    if ($bypassFilter || empty($alerts) || !usersHasReadableTable($pdo, 'alert_recipients')) {
        return $alerts;
    }

    $alertIds = [];
    foreach ($alerts as $alert) {
        $id = (int)($alert['id'] ?? 0);
        if ($id > 0) {
            $alertIds[] = $id;
        }
    }
    $alertIds = array_values(array_unique($alertIds));
    if (empty($alertIds)) {
        return $alerts;
    }

    try {
        $ph = implode(',', array_fill(0, count($alertIds), '?'));
        $targetedStmt = $pdo->prepare("SELECT DISTINCT alert_id FROM alert_recipients WHERE alert_id IN ({$ph})");
        $targetedStmt->execute($alertIds);
        $targetedRows = $targetedStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $targetedSet = [];
        foreach ($targetedRows as $rowId) {
            $targetedSet[(int)$rowId] = true;
        }
        if (empty($targetedSet)) {
            return $alerts;
        }

        $allowedSet = [];
        if ($userId > 0) {
            $allowedStmt = $pdo->prepare("SELECT DISTINCT alert_id FROM alert_recipients WHERE user_id = ? AND alert_id IN ({$ph})");
            $allowedStmt->execute(array_merge([$userId], $alertIds));
            $allowedRows = $allowedStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($allowedRows as $rowId) {
                $allowedSet[(int)$rowId] = true;
            }
        }

        $filtered = [];
        foreach ($alerts as $alert) {
            $id = (int)($alert['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $isTargeted = isset($targetedSet[$id]);
            if (!$isTargeted || isset($allowedSet[$id])) {
                $filtered[] = $alert;
            }
        }
        return $filtered;
    } catch (Throwable $e) {
        // Fallback: keep alerts visible if filter table is unhealthy.
        return $alerts;
    }
}

function usersNormalizeAlertMeta(array $alert): array {
    $title = strtolower((string)($alert['title'] ?? ''));
    $source = strtolower((string)($alert['source'] ?? ''));
    $type = strtolower((string)($alert['type'] ?? ''));
    $severity = strtolower((string)($alert['severity'] ?? ''));
    $categoryName = (string)($alert['category_name'] ?? 'General');

    if ($categoryName === '' || strtolower($categoryName) === 'general') {
        if (strpos($title, 'earthquake') !== false || $source === 'phivolcs' || $type === 'earthquake') {
            $categoryName = 'Earthquake';
        } elseif (strpos($title, 'weather') !== false || $source === 'pagasa' || $type === 'weather') {
            $categoryName = 'Weather';
        } else {
            $categoryName = 'General';
        }
    }

    if (in_array($severity, ['critical', 'extreme'], true)) {
        $alert['category'] = 'Emergency Alert';
        $alert['category_color'] = '#e74c3c';
        $alert['category_icon'] = 'fas fa-triangle-exclamation';
    }

    $alert['category_name'] = $categoryName;
    return $alert;
}

// Database connection
try {
    if (file_exists(__DIR__ . '/../../ADMIN/api/db_connect.php')) {
        require_once __DIR__ . '/../../ADMIN/api/db_connect.php';
    } else {
        require_once __DIR__ . '/db_connect.php';
    }

    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Database connection failed');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'alerts' => []
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Get query parameters
$status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'active';
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
$lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
$category = isset($_GET['category']) && $_GET['category'] !== '' && $_GET['category'] !== 'all' ? trim($_GET['category']) : null;
$timeFilter = isset($_GET['time_filter']) && in_array($_GET['time_filter'], ['24h', 'week', 'month', 'year', 'all'], true) ? $_GET['time_filter'] : '24h';
$severityFilter = isset($_GET['severity_filter']) && in_array($_GET['severity_filter'], ['emergency_only', 'warnings_only'], true) ? $_GET['severity_filter'] : null;
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);
$isAdminSession = (int)($_SESSION['admin_user_id'] ?? 0) > 0;

// Resolve requested language (user preference / device language)
$targetLanguage = resolveTargetLanguage($pdo);
$translationHelperAvailable = false;
$translationAttempted = false;
$translationSuccessCount = 0;
$alertsTable = usersResolveAlertsTable($pdo);
$hasCategoryTable = usersHasReadableTable($pdo, 'alert_categories');
$hasSeverityCol = usersTableHasColumn($pdo, $alertsTable, 'severity');
$hasSourceCol = usersTableHasColumn($pdo, $alertsTable, 'source');
$hasTypeCol = usersTableHasColumn($pdo, $alertsTable, 'type');
$hasCategoryCol = usersTableHasColumn($pdo, $alertsTable, 'category');

// Base query
$query = "
    SELECT 
        a.id,
        a.title,
        a.message,
        a.content,
        " . ($hasSeverityCol ? "a.severity" : "'' AS severity") . ",
        " . ($hasSourceCol ? "a.source" : "'' AS source") . ",
        " . ($hasTypeCol ? "a.type" : "'' AS type") . ",
        " . ($hasCategoryCol ? "a.category" : "'' AS category") . ",
        a.status,
        a.created_at,
        a.updated_at,
";
if ($hasCategoryTable) {
    $query .= "
        COALESCE(ac.name, 'General') as category_name,
        COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
        COALESCE(ac.color, '#95a5a6') as category_color
    FROM {$alertsTable} a
    LEFT JOIN alert_categories ac ON a.category_id = ac.id
    WHERE a.status = :status
";
} else {
    $query .= "
        COALESCE(a.category, 'General') as category_name,
        'fa-exclamation-triangle' as category_icon,
        '#95a5a6' as category_color
    FROM {$alertsTable} a
    WHERE a.status = :status
";
}

$params = [':status' => $status];

if ($category) {
    if ($hasCategoryTable) {
        $query .= " AND (ac.name = :category OR a.category = :category)";
    } else {
        $query .= " AND a.category = :category";
    }
    $params[':category'] = $category;
}

// Apply time filter (only when not loading new alerts via last_id)
if ($lastId === 0) {
    switch ($timeFilter) {
        case '24h':
            $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            break;
        case 'week':
            $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        case 'all':
        default:
            // No time restriction
            break;
    }
}

if ($severityFilter === 'emergency_only') {
    $query .= " AND (a.category = 'Emergency Alert' OR a.title LIKE '%[EXTREME]%' OR a.title LIKE '%EXTREME%')";
} elseif ($severityFilter === 'warnings_only') {
    $query .= " AND a.category = 'Warning'";
}

if ($lastId > 0) {
    $query .= " AND a.id > :last_id";
    $params[':last_id'] = $lastId;
}

$query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT " . (int)$limit;

// Execute query
$alerts = [];
try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($alerts)) {
        $alerts = [];
    }
} catch (PDOException $e) {
    error_log("Error fetching alerts: " . $e->getMessage());
    $alerts = [];
}

// Enforce recipient visibility for targeted dispatches.
$alerts = usersFilterAlertsByRecipientMap($pdo, $alerts, $sessionUserId, $isAdminSession);

// Apply server-side translation for alerts feed
$translationHelper = null;
if (file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
    require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
    if (class_exists('AlertTranslationHelper')) {
        $translationHelper = new AlertTranslationHelper($pdo);
        $translationHelperAvailable = true;
    }
}

if ($translationHelper && $targetLanguage !== 'en' && !empty($alerts)) {
    $translationAttempted = true;
    foreach ($alerts as &$alert) {
        $originalTitle = (string)($alert['title'] ?? '');
        $originalMessage = (string)($alert['message'] ?? ($alert['content'] ?? ''));
        if ($originalTitle === '' && $originalMessage === '') {
            continue;
        }

        $translatedAlert = $translationHelper->getTranslatedAlert(
            (int)$alert['id'],
            $targetLanguage,
            $originalTitle,
            $originalMessage
        );

        if (is_array($translatedAlert) && !empty($translatedAlert['title']) && !empty($translatedAlert['message'])) {
            $alert['title'] = $translatedAlert['title'];
            $alert['message'] = $translatedAlert['message'];
            $alert['content'] = $translatedAlert['message'];
            $translationSuccessCount++;
        }
    }
    unset($alert);
}

// Format timestamps
foreach ($alerts as &$alert) {
    $alert = usersNormalizeAlertMeta($alert);
    $alert['timestamp'] = $alert['created_at'] ?? '';
    $alert['time_ago'] = getTimeAgo($alert['created_at'] ?? '');
}
unset($alert);

echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'count' => count($alerts),
    'timestamp' => date('c'),
    'language' => $targetLanguage,
    'translation_applied' => $translationSuccessCount > 0,
    'translation_helper_available' => $translationHelperAvailable,
    'debug' => [
        'target_language' => $targetLanguage,
        'alerts_count' => count($alerts),
        'translation_attempted' => $translationAttempted,
        'translation_success' => $translationSuccessCount > 0,
        'translated_alerts' => $translationSuccessCount,
        'ai_service_available' => $translationHelperAvailable
    ]
], JSON_UNESCAPED_UNICODE);
?>
