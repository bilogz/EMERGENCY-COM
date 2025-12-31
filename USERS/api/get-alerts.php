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
            COALESCE(ac.color, '#95a5a6') as category_color
        FROM alerts a
        LEFT JOIN alert_categories ac ON a.category_id = ac.id
        WHERE a.status = :status
    ";
    
    $params = [':status' => $status];
    
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
    
    // Format timestamps
    foreach ($alerts as &$alert) {
        $alert['timestamp'] = $alert['created_at'];
        $alert['time_ago'] = getTimeAgo($alert['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'count' => count($alerts),
        'timestamp' => date('c')
    ]);
    
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

