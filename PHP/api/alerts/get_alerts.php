<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../shared/db_connect.php';

/** @var PDO $pdo */

// Allow GET only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse::error("Invalid request method. Use GET.", 405);
}

try {
    // Get query parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $severity = isset($_GET['severity']) ? $_GET['severity'] : null;
    $activeOnly = isset($_GET['active_only']) ? $_GET['active_only'] : '1';

    // Build query
    $query = "
        SELECT 
            a.id,
            a.category_id,
            a.title,
            a.message,
            ac.name as category,
            a.area,
            a.content,
            a.source,
            a.status,
            a.severity,
            a.latitude,
            a.longitude,
            a.is_viewed,
            a.incident_id,
            a.created_at,
            a.updated_at
        FROM alerts a
        LEFT JOIN alert_categories ac ON a.category_id = ac.id
        WHERE 1=1
    ";

    $params = [];

    // Filter by active status
    if ($activeOnly === '1') {
        $query .= " AND a.status = 'active'";
    }

    // Filter by category
    if ($category) {
        $query .= " AND (ac.name LIKE :category OR a.category LIKE :category)";
        $params[':category'] = "%$category%";
    }

    // Filter by severity
    if ($severity) {
        $query .= " AND a.severity = :severity";
        $params[':severity'] = $severity;
    }

    // Order by created_at descending (newest first)
    $query .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);

    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_viewed to boolean
    $alerts = array_map(function($alert) {
        $alert['is_viewed'] = (bool)$alert['is_viewed'];
        return $alert;
    }, $alerts);

    apiResponse::success(['data' => $alerts], "Alerts retrieved successfully");

} catch (PDOException $e) {
    error_log("Get Alerts DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    error_log("Get Alerts Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500);
}
