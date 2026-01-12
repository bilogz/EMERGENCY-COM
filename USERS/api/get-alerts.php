<?php
/**
 * Get Alerts API - RESET VERSION
 * Returns alerts from database (simplified version)
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate, max-age=0');

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
$timeFilter = isset($_GET['time_filter']) && in_array($_GET['time_filter'], ['recent', 'older', 'all']) ? $_GET['time_filter'] : 'recent';
$severityFilter = isset($_GET['severity_filter']) && in_array($_GET['severity_filter'], ['emergency_only', 'warnings_only']) ? $_GET['severity_filter'] : null;

// Simple query
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

// Format timestamps
foreach ($alerts as &$alert) {
    $alert['timestamp'] = $alert['created_at'] ?? '';
    $alert['time_ago'] = getTimeAgo($alert['created_at'] ?? '');
}
unset($alert);

// Return response
echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'count' => count($alerts),
    'timestamp' => date('c'),
    'language' => 'en'
], JSON_UNESCAPED_UNICODE);

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
