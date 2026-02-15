<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';   // should set up $pdo
require_once __DIR__ . '/apiResponse.php';  // shared response helper (prevents redeclare issues)

/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse::error('Method Not Allowed. Use POST.', 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
if (stripos((string)$contentType, 'application/json') === false) {
    apiResponse::error('Invalid Content-Type. Use application/json.', 400);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    apiResponse::error('Invalid JSON input.', 400);
}

if (!isset($data['user_id'], $data['device_id']) || (!isset($data['fcm_token']) && !isset($data['push_token']))) {
    apiResponse::error('Missing required fields: user_id, device_id, fcm_token (or push_token).', 400);
}

$userId = (int)$data['user_id'];
$deviceId = trim((string)$data['device_id']);
$fcmToken = trim((string)($data['fcm_token'] ?? $data['push_token'] ?? ''));

if ($userId <= 0) {
    apiResponse::error('User ID must be a valid integer.', 400);
}
if ($deviceId === '') {
    apiResponse::error('Device ID cannot be empty.', 400);
}
if ($fcmToken === '') {
    apiResponse::error('FCM Token cannot be empty.', 400);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    apiResponse::error('Database connection failed.', 500);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO user_devices (user_id, device_id, fcm_token, is_active, last_active)
        VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            fcm_token = VALUES(fcm_token),
            is_active = 1,
            last_active = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$userId, $deviceId, $fcmToken]);

    apiResponse::success(null, 'Device token updated successfully.');
} catch (PDOException $e) {
    error_log("FCM Update Error: " . $e->getMessage() . " | User: $userId | Device: $deviceId");
    apiResponse::error('Database error occurred while saving token.', 500);
}
