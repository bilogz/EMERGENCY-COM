<?php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'], $data['device_id'], $data['fcm_token'])) {
    apiResponse::error('User ID, Device ID, and FCM Token are required.', 400);
}

$userId = $data['user_id'];
$deviceId = $data['device_id'];
$fcmToken = $data['fcm_token'];

try {

    $stmt = $pdo->prepare("
        INSERT INTO user_devices 
            (user_id, device_id, fcm_token, is_active)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            fcm_token = VALUES(fcm_token),
            is_active = 1,
            last_active = CURRENT_TIMESTAMP
    ");

    $stmt->execute([$userId, $deviceId, $fcmToken]);

    apiResponse::success(null, 'Device token saved successfully.');

} catch (PDOException $e) {

    error_log("FCM Token Update DB Error: " . $e->getMessage());
    apiResponse::error('Failed to save device token.', 500);
}
?>
