<?php
header('Content-Type: application/json');
require_once '../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$deviceId = isset($input['device_id']) ? trim($input['device_id']) : '';
$fcmToken = isset($input['fcm_token']) ? trim($input['fcm_token']) : (isset($input['push_token']) ? trim($input['push_token']) : '');
$deviceName = isset($input['device_name']) ? trim($input['device_name']) : 'Unnamed Device';

if ($userId <= 0 || empty($deviceId) || empty($fcmToken)) {
    apiResponse::error('user_id, device_id, and fcm_token (or push_token) are required.', 400);
}

try {
    $sql = "
        INSERT INTO user_devices (user_id, device_id, fcm_token, device_name, is_active, last_active)
        VALUES (?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            fcm_token = VALUES(fcm_token),
            device_name = VALUES(device_name),
            is_active = 1,
            last_active = NOW()
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $deviceId, $fcmToken, $deviceName]);

    apiResponse::success(null, 'Device registered successfully.');

} catch (PDOException $e) {
    error_log("Device Registration DB Error for user_id {$userId}: " . $e->getMessage());
    apiResponse::error('Database update failed during device registration.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log("Device Registration Error: " . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
