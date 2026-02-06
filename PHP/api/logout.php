<?php
// logout.php
header('Content-Type: application/json');

require_once 'db_connect.php';
/** @var PDO $pdo */

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['device_id'])) {
    apiResponse::error('User ID and Device ID are required.', 400);
}

$userId = $data['user_id'];
$deviceId = $data['device_id'];

try {
    $stmt = $pdo->prepare("UPDATE user_devices SET is_active = 0 WHERE user_id = ? AND device_id = ?");
    $stmt->execute([$userId, $deviceId]);

    apiResponse::success(null, 'Logged out successfully.');
} catch (PDOException $e) {
    error_log("Logout Error: " . $e->getMessage());
    apiResponse::error('Logout failed.', 500, $e->getMessage());
}
?>
