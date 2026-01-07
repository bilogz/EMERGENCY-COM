<?php
/**
 * Mobile App Alerts API
 * Returns alerts from admin database, filtered by user subscriptions and preferences
 * This endpoint connects to the same database that admin uses to send alerts
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

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
    
    // If user is logged in, get translated alerts in their preferred language
    if ($userId && $userId > 0 && !empty($alerts)) {
        // Get user's preferred language
        $userLang = 'en';
        if ($subscription && !empty($subscription['preferred_language'])) {
            $userLang = $subscription['preferred_language'];
        } else {
            // Try user_preferences table
            $prefStmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
            $prefStmt->execute([$userId]);
            $pref = $prefStmt->fetch();
            if ($pref && !empty($pref['preferred_language'])) {
                $userLang = $pref['preferred_language'];
            }
        }
        
        // Load translation helper if available
        if (file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
            require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
            $translationHelper = new AlertTranslationHelper($pdo);
            
            // Translate alerts to user's preferred language
            foreach ($alerts as &$alert) {
                if ($userLang !== 'en') {
                    $translated = $translationHelper->getTranslatedAlert($alert['id'], $userLang, $userId);
                    if ($translated) {
                        $alert['title'] = $translated['translated_title'] ?? $alert['title'];
                        $alert['message'] = $translated['translated_content'] ?? $alert['message'];
                        $alert['content'] = $translated['translated_content'] ?? $alert['content'];
                    }
                }
            }
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
        'timestamp' => date('c')
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

