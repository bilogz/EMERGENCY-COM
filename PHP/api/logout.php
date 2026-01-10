<?php
// logout.php
header('Content-Type: application/json');

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

// We need both user_id and device_id to know exactly which device to deactivate
if (!isset($data['user_id']) || !isset($data['device_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and Device ID are required.']);
    exit();
}

$userId = $data['user_id'];
$deviceId = $data['device_id'];

try {
    // Mark the specific device as inactive
    $stmt = $pdo->prepare("UPDATE user_devices SET is_active = 0 WHERE user_id = ? AND device_id = ?");
    $stmt->execute([$userId, $deviceId]);

    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Logout failed.']);
}
?>