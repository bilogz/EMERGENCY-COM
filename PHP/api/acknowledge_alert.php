<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_connect.php';

/** @var PDO $pdo */

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error("Invalid request method.", 405);
}

// Get the POST data (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data)) {
    apiResponse::error("Invalid JSON input.", 400);
}

$alert_id = $data['alert_id'] ?? null;
$user_id  = $data['user_id'] ?? null;
$response_status = $data['response_status'] ?? 'safe';
$response_notes = $data['response_notes'] ?? null;

// Extract latitude and longitude
$latitude  = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

// Validate IDs
if (!is_numeric($alert_id) || !is_numeric($user_id)) {
    apiResponse::error("Invalid alert_id or user_id.", 400);
}

// Validate response status
$validStatuses = ['safe', 'need-help', 'evacuated', 'not-affected'];
if (!in_array($response_status, $validStatuses)) {
    apiResponse::error("Invalid response_status. Must be one of: " . implode(', ', $validStatuses), 400);
}

// Validate coordinates if provided
if ($latitude !== null && !is_numeric($latitude)) {
    apiResponse::error("Invalid latitude.", 400);
}

if ($longitude !== null && !is_numeric($longitude)) {
    apiResponse::error("Invalid longitude.", 400);
}

try {

    $query = "
        INSERT INTO alert_acknowledgments
        (alert_id, user_id, response_status, response_notes, latitude, longitude, acknowledged_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            response_status = VALUES(response_status),
            response_notes = VALUES(response_notes),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            acknowledged_at = NOW()
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        (int)$alert_id,
        (int)$user_id,
        $response_status,
        $response_notes,
        $latitude,
        $longitude
    ]);

    apiResponse::success(null, "Alert acknowledged successfully");

} catch (PDOException $e) {
    error_log("Acknowledge Alert DB Error: " . $e->getMessage());
    apiResponse::error("Database error occurred.", 500);
} catch (Exception $e) {
    apiResponse::error("An unexpected error occurred.", 500);
}