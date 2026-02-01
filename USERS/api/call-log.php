<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';

if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS call_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            call_id VARCHAR(64) NOT NULL,
            room VARCHAR(128) DEFAULT NULL,
            role VARCHAR(16) NOT NULL,
            event VARCHAR(32) NOT NULL,
            user_session_id VARCHAR(128) DEFAULT NULL,
            admin_id INT DEFAULT NULL,
            duration_sec INT DEFAULT NULL,
            location_json LONGTEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_call_id (call_id),
            INDEX idx_event (event),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to initialize call logs table']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$callId = $input['callId'] ?? null;
$room = $input['room'] ?? null;
$role = $input['role'] ?? null;
$event = $input['event'] ?? null;
$durationSec = isset($input['durationSec']) ? (int)$input['durationSec'] : null;
$location = $input['location'] ?? null;

if (!$callId || !$role || !$event) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$userSessionId = $_SESSION['user_id'] ?? null;
$adminId = $_SESSION['admin_user_id'] ?? null;

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

$locationJson = null;
if ($location !== null) {
    $locationJson = json_encode($location);
}

$stmt = $pdo->prepare("INSERT INTO call_logs (call_id, room, role, event, user_session_id, admin_id, duration_sec, location_json, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $callId,
    $room,
    $role,
    $event,
    $userSessionId,
    $adminId,
    $durationSec,
    $locationJson,
    $ipAddress,
    $userAgent
]);

echo json_encode(['success' => true]);
