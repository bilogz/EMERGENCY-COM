<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_connect.php';

/** @var PDO $pdo */

// Get the POST data (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    apiResponse::error("Invalid JSON input.", 400);
}

$alert_id = $data['alert_id'] ?? null;
$user_id  = $data['user_id'] ?? null;

// Use 'received' as default status for this endpoint
$status = $data['status'] ?? 'received';

if ($alert_id && $user_id) {
    try {

        $query = "
            INSERT INTO alert_acknowledgments 
            (alert_id, user_id, status, acknowledged_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                acknowledged_at = NOW()
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$alert_id, $user_id, $status]);

        apiResponse::success(null, "Alert acknowledged successfully");

    } catch (PDOException $e) {
        error_log("Acknowledge Alert DB Error: " . $e->getMessage());
        apiResponse::error("Database error occurred.", 500);
    } catch (Exception $e) {
        apiResponse::error("An unexpected error occurred.", 500);
    }
} else {
    apiResponse::error("Missing alert_id or user_id", 400);
}
?>
