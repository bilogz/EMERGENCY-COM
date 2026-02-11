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
    
    // Build base query - get alerts from admin database
    $query = "
        SELECT 
            a.id,
            a.title,
            a.message,
            a.content,
            a.status,
            a.created_at,
            a.updated_at,
            COALESCE(ac.name, 'General') as category_name,
            COALESCE(ac.icon, 'fa-exclamation-triangle') as category_icon,
            COALESCE(ac.color, '#95a5a6') as category_color
        FROM alerts a
        LEFT JOIN alert_categories ac ON a.category_id = ac.id
        WHERE a.status = 'active'
    ";
    
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
                $categoryConditions[] = "ac.name = ?";
                $params[] = ucfirst(strtolower($cat));
            }
            
            if (!empty($categoryConditions)) {
                $query .= " AND (" . implode(' OR ', $categoryConditions) . " OR ac.name IS NULL)";
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


