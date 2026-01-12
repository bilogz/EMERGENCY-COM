<?php
/**
 * Get Live Alerts API with Built-in Translation
 * Returns real-time alerts from the database with automatic translation support
 */

// Prevent any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR])) {
        error_log("FATAL ERROR in get-alerts.php: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        
        if (ob_get_level()) {
            @ob_clean();
        }
        
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        
        try {
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error occurred',
                'alerts' => []
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $jsonError) {
            echo '{"success":false,"message":"Fatal error occurred","alerts":[]}';
        }
        
        if (ob_get_level()) {
            @ob_end_flush();
        }
        exit();
    }
});

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

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
    if (ob_get_level()) {
        @ob_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'alerts' => []
    ], JSON_UNESCAPED_UNICODE);
    if (ob_get_level()) {
        @ob_end_flush();
    }
    exit();
}

// Load translation helper (optional - will work without it)
$translationHelper = null;
if (file_exists(__DIR__ . '/../../ADMIN/api/alert-translation-helper.php')) {
    try {
        require_once __DIR__ . '/../../ADMIN/api/alert-translation-helper.php';
        $translationHelper = new AlertTranslationHelper($pdo);
    } catch (Throwable $e) {
        error_log("Translation helper not available: " . $e->getMessage());
        $translationHelper = null;
    }
}

// Determine target language
$targetLanguage = 'en'; // Default
try {
    // Priority 1: Query parameter (from UI language selector)
    if (isset($_GET['lang']) && !empty($_GET['lang'])) {
        $targetLanguage = strtolower(trim($_GET['lang']));
    }
    // Priority 2: User preference (if logged in)
    elseif (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        try {
            $stmt = $pdo->prepare("SELECT preferred_language FROM user_preferences WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $pref = $stmt->fetch();
            if ($pref && !empty($pref['preferred_language'])) {
                $targetLanguage = $pref['preferred_language'];
            }
        } catch (PDOException $e) {
            // Fallback to users table
            try {
                $stmt = $pdo->prepare("SELECT preferred_language FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                if ($user && !empty($user['preferred_language'])) {
                    $targetLanguage = $user['preferred_language'];
                }
            } catch (PDOException $e2) {
                // Ignore
            }
        }
    }
    // Priority 3: Browser language
    if ($targetLanguage === 'en' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if (preg_match('/^([a-z]{2})/i', $acceptLang, $matches)) {
            $targetLanguage = strtolower($matches[1]);
        }
    }
} catch (Exception $e) {
    error_log("Error determining language: " . $e->getMessage());
    $targetLanguage = 'en';
}

// Get query parameters
$category = isset($_GET['category']) && $_GET['category'] !== '' && $_GET['category'] !== 'all' ? trim($_GET['category']) : null;
$status = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : 'active';
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
$lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
$timeFilter = isset($_GET['time_filter']) && in_array($_GET['time_filter'], ['recent', 'older', 'all']) ? $_GET['time_filter'] : 'recent';
$severityFilter = isset($_GET['severity_filter']) && in_array($_GET['severity_filter'], ['emergency_only', 'warnings_only']) ? $_GET['severity_filter'] : null;

// Build query
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
    WHERE a.status = :status
";

$params = [':status' => $status];

// Category filter
if ($category) {
    $query .= " AND (ac.name = :category OR a.category = :category)";
    $params[':category'] = $category;
}

// Time filter
if ($timeFilter === 'recent' && $lastId == 0) {
    $query .= " AND a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
} elseif ($timeFilter === 'older' && $lastId == 0) {
    $query .= " AND a.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
}

// Severity filter
if ($severityFilter === 'emergency_only') {
    $query .= " AND (a.category = 'Emergency Alert' OR a.title LIKE '%[EXTREME]%' OR a.title LIKE '%EXTREME%')";
} elseif ($severityFilter === 'warnings_only') {
    $query .= " AND a.category = 'Warning'";
}

// Incremental updates
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

// Apply translations if needed
if ($targetLanguage && $targetLanguage !== 'en' && $translationHelper && !empty($alerts)) {
    foreach ($alerts as &$alert) {
        try {
            if (!isset($alert['id'])) {
                continue;
            }
            
            $translated = $translationHelper->getTranslatedAlert($alert['id'], $targetLanguage, null, $targetLanguage);
            
            if ($translated && isset($translated['language']) && $translated['language'] !== 'en') {
                $alert['title'] = $translated['title'];
                if (isset($translated['message']) && !empty($translated['message'])) {
                    $alert['message'] = $translated['message'];
                    if (isset($translated['content']) && !empty($translated['content'])) {
                        $alert['content'] = $translated['content'];
                    } else {
                        $alert['content'] = $translated['message'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Translation error for alert {$alert['id']}: " . $e->getMessage());
        }
    }
    unset($alert);
}

// Format timestamps
foreach ($alerts as &$alert) {
    $alert['timestamp'] = $alert['created_at'] ?? '';
    $alert['time_ago'] = getTimeAgo($alert['created_at'] ?? '');
}
unset($alert);

// Clean output and return response
if (ob_get_level()) {
    @ob_clean();
}

echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'count' => count($alerts),
    'timestamp' => date('c'),
    'language' => $targetLanguage,
    'translation_applied' => ($targetLanguage !== 'en' && $translationHelper !== null)
], JSON_UNESCAPED_UNICODE);

if (ob_get_level()) {
    @ob_end_flush();
}

/**
 * Calculate time ago string
 */
function getTimeAgo($datetime) {
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
