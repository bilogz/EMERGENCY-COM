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
            call_id VARCHAR(100) NOT NULL COMMENT 'Unique call identifier',
            user_id INT DEFAULT NULL COMMENT 'User who initiated the call (for user calls)',
            role VARCHAR(20) NOT NULL COMMENT 'user, admin',
            event VARCHAR(50) NOT NULL COMMENT 'started, incoming, connected, ended, cancelled, declined, accepted',
            timestamp BIGINT NOT NULL COMMENT 'Unix timestamp of event',
            duration_sec INT DEFAULT NULL COMMENT 'Call duration in seconds (for ended events)',
            location_data JSON DEFAULT NULL COMMENT 'Location data at time of call',
            room VARCHAR(128) DEFAULT NULL COMMENT 'Room name for signaling',
            metadata JSON DEFAULT NULL COMMENT 'Additional event data',
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_call_id (call_id),
            INDEX idx_user_id (user_id),
            INDEX idx_role (role),
            INDEX idx_event (event),
            INDEX idx_timestamp (timestamp),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    error_log('ADMIN call_logs table creation note: ' . $e->getMessage());
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$callId = $input['callId'] ?? null;
$userId = $input['userId'] ?? null;
$room = $input['room'] ?? null;
$role = $input['role'] ?? null;
$event = $input['event'] ?? null;
$durationSec = isset($input['durationSec']) ? (int)$input['durationSec'] : null;
$location = $input['location'] ?? null;
$metadata = $input['metadata'] ?? null;

if (!$callId || !$role || !$event) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$userSessionId = $_SESSION['user_id'] ?? null;
$adminId = $_SESSION['admin_user_id'] ?? null;
$adminUsername = $_SESSION['admin_username'] ?? null;

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

$timestamp = time();

// Collect extra fields into metadata so you can see who accepted, etc.
$knownKeys = ['callId', 'userId', 'room', 'role', 'event', 'durationSec', 'location', 'metadata'];
$extraMeta = array_diff_key($input, array_flip($knownKeys));

$metaArr = [];
if (is_array($metadata)) {
    $metaArr = $metadata;
}
if (is_array($extraMeta) && !empty($extraMeta)) {
    $metaArr = array_merge($metaArr, $extraMeta);
}
if ($adminId !== null) $metaArr['adminId'] = $adminId;
if ($adminUsername !== null) $metaArr['adminUsername'] = $adminUsername;

$locationJson = null;
if ($location !== null) {
    $locationJson = json_encode($location);
}

$metadataJson = null;
if (!empty($metaArr)) {
    $metadataJson = json_encode($metaArr);
}

try {
    $stmt = $pdo->prepare("INSERT INTO call_logs (call_id, user_id, role, event, timestamp, duration_sec, location_data, room, metadata, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $callId,
        $userId,
        $role,
        $event,
        $timestamp,
        $durationSec,
        $locationJson,
        $room,
        $metadataJson,
        $ipAddress,
        $userAgent
    ]);
} catch (PDOException $e) {
    // Fallback to legacy schema
    try {
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
    } catch (PDOException $e2) {
        // Log error but don't fail the request - call logging is not critical
        error_log('Call log insert error: ' . $e2->getMessage());
        // Still return success since call logging is not critical
    }
}

echo json_encode(['success' => true]);
