<?php
/**
 * Get Live Alerts API
 * Returns real-time alerts from the database for Quezon City
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

require_once 'db_connect.php';

// Load translation helper if available
$translationHelper = null;
if (file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
    require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
    try {
        $translationHelper = new AlertTranslationHelper($pdo);
    } catch (Exception $e) {
        error_log("Failed to initialize AlertTranslationHelper: " . $e->getMessage());
    }
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
    
    // Get query parameters
    $category = $_GET['category'] ?? null;
    $status = $_GET['status'] ?? 'active';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $lastUpdate = $_GET['last_update'] ?? null;
    
    // Get user area for filtering (if logged in)
    $userArea = null;
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            try {
                $areaStmt = $pdo->prepare("SELECT barangay FROM users WHERE id = ? LIMIT 1");
                $areaStmt->execute([$userId]);
                $user = $areaStmt->fetch();
                if ($user && !empty($user['barangay'])) {
                    $userArea = $user['barangay'];
                }
            } catch (PDOException $e) {
                error_log("Error getting user area: " . $e->getMessage());
            }
        }
    }
    
    // Check if area column exists in alerts table
    $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'area'");
    $hasAreaColumn = $stmt->rowCount() > 0;
    
    // Build query - prioritize recent alerts
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
            COALESCE(ac.color, '#95a5a6') as category_color";
    
    // Add area and category if columns exist
    if ($hasAreaColumn) {
        $stmt = $pdo->query("SHOW COLUMNS FROM alerts LIKE 'category'");
        $hasCategoryColumn = $stmt->rowCount() > 0;
        if ($hasCategoryColumn) {
            $query .= ", a.area, a.category";
        }
    }
    
    $query .= "
        FROM alerts a
        LEFT JOIN alert_categories ac ON a.category_id = ac.id
        WHERE a.status = :status
    ";
    
    $params = [':status' => $status];
    
    // Filter by area if user is logged in and area column exists
    if ($userArea && $hasAreaColumn) {
        // Show alerts for user's area OR alerts with NULL area (city-wide)
        $query .= " AND (a.area = :user_area OR a.area IS NULL OR a.area = '')";
        $params[':user_area'] = $userArea;
    }
    
    // Filter by category if provided
    if ($category && $category !== 'all') {
        $query .= " AND (ac.name = :category OR (:category = 'General' AND ac.name IS NULL))";
        $params[':category'] = $category;
    }
    
    // Get only new alerts if lastId is provided (for incremental updates)
    if ($lastId > 0) {
        $query .= " AND a.id > :last_id";
        $params[':last_id'] = $lastId;
    }
    
    // Alternative: check by updated_at timestamp for more reliable real-time updates
    if ($lastUpdate && $lastId == 0) {
        // Convert ISO 8601 timestamp to MySQL datetime format
        $lastUpdateTime = date('Y-m-d H:i:s', strtotime($lastUpdate));
        $query .= " AND a.updated_at > :last_update";
        $params[':last_update'] = $lastUpdateTime;
    }
    
    $query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $alerts = $stmt->fetchAll();
    
    // Get language preference from query parameter
    $targetLanguage = $_GET['lang'] ?? $_GET['language'] ?? null;
    
    // If no language in query parameter, try to get from session (for logged-in users)
    if (!$targetLanguage) {
        // Start session only if not already started (for logged-in users)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                // Try to get from user preferences
                try {
                    $prefStmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
                    $prefStmt->execute([$userId]);
                    $pref = $prefStmt->fetch();
                    if ($pref && !empty($pref['preferred_language'])) {
                        $targetLanguage = $pref['preferred_language'];
                    }
                } catch (PDOException $e) {
                    // Ignore errors, just continue without translation
                    error_log("Error getting user language preference: " . $e->getMessage());
                }
            }
        }
    }
    
    // Translate alerts if language is specified and translation helper is available
    if ($targetLanguage && $targetLanguage !== 'en' && $translationHelper && !empty($alerts)) {
        foreach ($alerts as &$alert) {
            try {
                $translated = $translationHelper->getTranslatedAlert($alert['id'], $targetLanguage, null, $targetLanguage);
                if ($translated && isset($translated['title'])) {
                    $alert['title'] = $translated['title'];
                    $alert['message'] = $translated['message'] ?? $alert['message'];
                    if (isset($translated['message']) && !empty($alert['content'])) {
                        // Try to get full content translation if available
                        $alert['content'] = $translated['message'];
                    }
                }
            } catch (Exception $e) {
                // If translation fails, use original alert
                error_log("Translation error for alert {$alert['id']}: " . $e->getMessage());
            }
        }
        unset($alert); // Break reference
    }
    
    // Format timestamps
    foreach ($alerts as &$alert) {
        $alert['timestamp'] = $alert['created_at'];
        $alert['time_ago'] = getTimeAgo($alert['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'count' => count($alerts),
        'timestamp' => date('c'),
        'language' => $targetLanguage ?? 'en'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Get Alerts API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'alerts' => []
    ]);
} catch (Exception $e) {
    error_log("Get Alerts API Error: " . $e->getMessage());
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

