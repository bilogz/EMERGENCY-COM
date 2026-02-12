<?php
header('Content-Type: application/json');

// Ensure this file exists in the same directory
require_once 'db_connect.php';
/** @var PDO $pdo */

// Helper class for consistent API responses
class apiResponse {
    public static function success($data, $message = null) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function error($message, $statusCode = 500) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

// 1. Get Input Data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 2. Validate Existence
if (!isset($data['user_id'], $data['device_id']) || (!isset($data['fcm_token']) && !isset($data['push_token']))) {
    apiResponse::error('Missing required fields: user_id, device_id, fcm_token (or push_token).', 400);
}

$userId = $data['user_id'];
$deviceId = $data['device_id'];
$fcmToken = $data['fcm_token'] ?? $data['push_token'];

// 3. Validate Data Types & Format
if (!is_numeric($userId)) {
    apiResponse::error('User ID must be a valid integer.', 400);
}

if (empty($deviceId)) {
    apiResponse::error('Device ID cannot be empty.', 400);
}

if (empty($fcmToken)) {
    apiResponse::error('FCM Token cannot be empty.', 400);
}

// Ensure database connection is established before usage
if (!isset($pdo)) {
    apiResponse::error('Database connection failed.', 500);
}

try {
    // 4. Upsert Logic (Insert or Update)
    // This efficiently handles new devices or updating the token for an existing device ID.
    $stmt = $pdo->prepare("
        INSERT INTO user_devices 
            (user_id, device_id, fcm_token, is_active, last_active)
        VALUES 
            (?, ?, ?, 1, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            fcm_token = VALUES(fcm_token),
            is_active = 1,
            last_active = CURRENT_TIMESTAMP
    ");

    $stmt->execute([$userId, $deviceId, $fcmToken]);

    apiResponse::success(null, 'Device token updated successfully.');

} catch (PDOException $e) {
    // 5. Secure Logging
    error_log("FCM Update Error: " . $e->getMessage() . " | User: $userId | Device: $deviceId");
    apiResponse::error('Database error occurred while saving token.', 500);
}
?>
