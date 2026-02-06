<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db_connect.php';   // must define $pdo

/** @var PDO $pdo */

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

try {
    // Note: 'location', 'latitude', 'longitude' are included as NULL fallbacks 
    // if they don't exist in your 'alerts' table yet. 
    // Update your schema to include them if the app needs real values.
    $sql = "
        SELECT 
            id,
            category_id AS category,
            title,
            message AS content,
            source,
            NULL AS location, 
            NULL AS latitude,
            NULL AS longitude,
            created_at AS timestamp
        FROM alerts
    ";

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

    apiResponse::success(["alerts" => $alerts], "OK");

} catch (PDOException $e) {
    error_log("Alerts DB Error: " . $e->getMessage());
    apiResponse::error("Failed to fetch alerts.", 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Alerts Error: " . $e->getMessage());
    apiResponse::error("An unexpected error occurred.", 500, $e->getMessage());
}
