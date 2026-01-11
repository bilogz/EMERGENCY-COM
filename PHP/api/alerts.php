<?php
// Prevent any output before headers
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Log errors instead of displaying them (prevents JSON parsing errors)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once 'db_connect.php';   // must define $pdo

// Clean any output buffer before JSON
ob_clean();

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

try {
    // Base query
    $sql = "
        SELECT 
            id,
            category_id AS category,   -- maps to Alert.category
            title,
            message      AS content,   -- maps to Alert.content
            source,                     -- new field for the source of the alert
            created_at   AS timestamp -- maps to Alert.timestamp
        FROM alerts
    ";

    // If user_id is provided, filter out categories they have disabled (is_active = 0)
    if ($userId) {
        $sql .= " WHERE category_id NOT IN (
                    SELECT category_id FROM user_subscriptions 
                    WHERE user_id = :user_id AND is_active = 0
                  )";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    if ($userId) {
        $stmt->bindParam(':user_id', $userId);
    }
    $stmt->execute();

    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    echo json_encode([
        "success" => true,
        "message" => "OK",
        "alerts"  => $alerts
    ]);
    
    // End output buffering
    if (ob_get_level()) {
        ob_end_flush();
    }

} catch (PDOException $e) {
    // Log error instead of exposing it
    error_log("Alerts API DB Error: " . $e->getMessage());
    
    // Ensure clean output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred",
        "alerts"  => []
    ]);
    
    // End output buffering
    if (ob_get_level()) {
        ob_end_flush();
    }
}