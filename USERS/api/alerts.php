<?php
/**
 * Mobile App Alerts API
 * Returns alerts from admin database, filtered by user subscriptions and preferences
 * This endpoint connects to the same database that admin uses to send alerts
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

function normalizeMobileAlertLanguage($language): string {
    $lang = strtolower(trim((string)$language));
    if ($lang === 'tl') {
        $lang = 'fil';
    }
    if ($lang !== '' && !preg_match('/^[a-z0-9_-]{2,15}$/', $lang)) {
        return '';
    }
    return $lang;
}

function detectMobileBrowserLanguage(): string {
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($acceptLanguage === '') {
        return 'en';
    }
    $first = trim(explode(',', $acceptLanguage)[0] ?? '');
    $base = strtolower(trim(explode('-', $first)[0] ?? ''));
    $lang = normalizeMobileAlertLanguage($base);
    return $lang !== '' ? $lang : 'en';
}

function usersAlertsHasReadableTable(PDO $pdo, string $tableName): bool {
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

function usersEnsureAlertsRuntimeTable(PDO $pdo, string $tableName): bool {
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

function usersResolveAlertsSourceTable(PDO $pdo): string {
    if (usersAlertsHasReadableTable($pdo, 'alerts')) {
        return 'alerts';
    }

    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (usersAlertsHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }
    foreach (['alerts_runtime', 'alerts_runtime_fallback'] as $candidate) {
        if (usersEnsureAlertsRuntimeTable($pdo, $candidate) && usersAlertsHasReadableTable($pdo, $candidate)) {
            return $candidate;
        }
    }

    return 'alerts';
}

function usersAlertsTableHasColumn(PDO $pdo, string $tableName, string $column): bool {
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

function normalizeMobileAlertMeta(array $alert): array {
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

// Use admin database connection to ensure we get alerts from the same source
if (file_exists(__DIR__ . '/../../ADMIN/api/db_connect.php')) {
    require_once __DIR__ . '/../../ADMIN/api/db_connect.php';
} else {
    require_once __DIR__ . '/db_connect.php';
}

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'alerts' => []
        ]);
        exit;
    }
    
    // Get user_id from query parameter (mobile app sends this)
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $alertsTable = usersResolveAlertsSourceTable($pdo);
    $hasCategoryTable = usersAlertsHasReadableTable($pdo, 'alert_categories');
    $hasSeverityCol = usersAlertsTableHasColumn($pdo, $alertsTable, 'severity');
    $hasSourceCol = usersAlertsTableHasColumn($pdo, $alertsTable, 'source');
    $hasTypeCol = usersAlertsTableHasColumn($pdo, $alertsTable, 'type');
    $hasCategoryCol = usersAlertsTableHasColumn($pdo, $alertsTable, 'category');
    
    // Build base query - get alerts from admin database
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
        WHERE a.status = 'active'
    ";
    } else {
        $query .= "
            COALESCE(a.category, 'General') as category_name,
            'fa-exclamation-triangle' as category_icon,
            '#95a5a6' as category_color
        FROM {$alertsTable} a
        WHERE a.status = 'active'
    ";
    }
    
    $params = [];
    
    // If user is logged in, filter by their subscriptions
    if ($userId && $userId > 0) {
        // Get user's subscription preferences
        $subStmt = $pdo->prepare("
            SELECT categories, preferred_language 
            FROM subscriptions 
            WHERE user_id = ? AND status = 'active'
            LIMIT 1
        ");
        $subStmt->execute([$userId]);
        $subscription = $subStmt->fetch();
        
        if ($subscription && !empty($subscription['categories'])) {
            // User has subscriptions - filter by subscribed categories
            $categories = explode(',', $subscription['categories']);
            $categories = array_map('trim', $categories);
            
            // Build category filter
            $categoryConditions = [];
            foreach ($categories as $cat) {
                $categoryConditions[] = $hasCategoryTable ? "ac.name = ?" : "a.category = ?";
                $params[] = ucfirst(strtolower($cat));
            }
            
            if (!empty($categoryConditions)) {
                if ($hasCategoryTable) {
                    $query .= " AND (" . implode(' OR ', $categoryConditions) . " OR ac.name IS NULL)";
                } else {
                    $query .= " AND (" . implode(' OR ', $categoryConditions) . ")";
                }
            }
        }
    }
    
    // Get query parameters
    $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 50;
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $lastUpdate = $_GET['last_update'] ?? null;
    
    // Get only new alerts if lastId is provided (for incremental updates)
    if ($lastId > 0) {
        $query .= " AND a.id > ?";
        $params[] = $lastId;
    }
    
    // Alternative: check by updated_at timestamp for more reliable real-time updates
    if ($lastUpdate && $lastId == 0) {
        $lastUpdateTime = date('Y-m-d H:i:s', strtotime($lastUpdate));
        $query .= " AND a.updated_at > ?";
        $params[] = $lastUpdateTime;
    }
    
    $query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $alerts = $stmt->fetchAll();
    
    // Resolve target language:
    // 1) explicit query parameter
    // 2) logged-in user's preference
    // 3) browser/device language
    $requestedLang = normalizeMobileAlertLanguage($_GET['lang'] ?? '');
    $userLang = $requestedLang !== '' ? $requestedLang : 'en';

    if ($userId && $userId > 0 && $userLang === 'en') {
        if (!empty($subscription['preferred_language'])) {
            $userLang = normalizeMobileAlertLanguage($subscription['preferred_language']) ?: 'en';
        } else {
            try {
                $prefStmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
                $prefStmt->execute([$userId]);
                $pref = $prefStmt->fetch();
                if ($pref && !empty($pref['preferred_language'])) {
                    $userLang = normalizeMobileAlertLanguage($pref['preferred_language']) ?: 'en';
                }
            } catch (Throwable $e) {
                // Keep fallback.
            }
        }
    }

    if ($userLang === 'en') {
        $userLang = detectMobileBrowserLanguage();
    }

    // Translate alerts server-side when non-English language is requested
    if (!empty($alerts) && $userLang !== 'en' && file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
        require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
        if (class_exists('AlertTranslationHelper')) {
            $translationHelper = new AlertTranslationHelper($pdo);
            foreach ($alerts as &$alert) {
                $sourceTitle = (string)($alert['title'] ?? '');
                $sourceMessage = (string)($alert['message'] ?? ($alert['content'] ?? ''));
                $translated = $translationHelper->getTranslatedAlert((int)$alert['id'], $userLang, $sourceTitle, $sourceMessage);
                if ($translated && !empty($translated['title']) && !empty($translated['message'])) {
                    $alert['title'] = $translated['title'];
                    $alert['message'] = $translated['message'];
                    $alert['content'] = $translated['message'];
                }
            }
            unset($alert);
        }
    }
    
    // Format timestamps
    foreach ($alerts as &$alert) {
        $alert = normalizeMobileAlertMeta($alert);
        $alert['timestamp'] = $alert['created_at'];
        $alert['time_ago'] = getTimeAgo($alert['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Alerts retrieved successfully',
        'alerts' => $alerts,
        'count' => count($alerts),
        'timestamp' => date('c'),
        'language' => $userLang
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Alerts API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'alerts' => []
    ]);
} catch (Exception $e) {
    error_log("Alerts API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'alerts' => []
    ]);
}

/**
 * Calculate time ago string
 */
function getTimeAgo($datetime) {
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


