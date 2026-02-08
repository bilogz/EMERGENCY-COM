<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_connect.php';

/** @var PDO $pdo */

// Get the POST data (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$alert_id = $data['alert_id'] ?? null;
$user_id = $data['user_id'] ?? null;

if ($alert_id && $user_id) {
    try {
        // Insert or update database
        // Using ON DUPLICATE KEY UPDATE to handle re-acknowledgements (updating timestamp)
        $query = "INSERT INTO alert_acknowledgements (alert_id, user_id, acknowledged_at) 
                  VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE acknowledged_at = NOW()";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$alert_id, $user_id]);

        apiResponse::success(null, "Alert acknowledged successfully");

    } catch (PDOException $e) {
        error_log("Acknowledge Alert DB Error: " . $e->getMessage());
        apiResponse::error("Database error: " . $e->getMessage(), 500);
    } catch (Exception $e) {
        apiResponse::error("An unexpected error occurred.", 500);
    }
} else {
    apiResponse::error("Missing alert_id or user_id", 400);
}
?>