<?php
session_start();
header('Content-Type: application/json');

// Use existing database connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

try {
    // Create enhanced call_logs table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS call_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            call_id VARCHAR(100) NOT NULL COMMENT 'Unique call identifier',
            user_id INT DEFAULT NULL COMMENT 'User who initiated the call (for user calls)',
            role VARCHAR(20) NOT NULL COMMENT 'user, admin',
            event VARCHAR(50) NOT NULL COMMENT 'started, incoming, connected, ended, cancelled, declined',
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
    // Table might already exist with different structure, that's okay
    error_log('Call log table creation note: ' . $e->getMessage());
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$callId = $input['callId'] ?? null;
$userId = $input['userId'] ?? $_SESSION['user_id'] ?? null;
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

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$timestamp = time();

$locationJson = null;
if ($location !== null) {
    $locationJson = json_encode($location);
}

$metadataJson = null;
if ($metadata !== null) {
    $metadataJson = json_encode($metadata);
}

// Try to use new table structure first, fall back to old structure
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
    // Fall back to old table structure for compatibility
    try {
        $stmt = $pdo->prepare("INSERT INTO call_logs (call_id, room, role, event, user_session_id, admin_id, duration_sec, location_json, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $callId,
            $room,
            $role,
            $event,
            $userId,
            null, // admin_id
            $durationSec,
            $locationJson,
            $ipAddress,
            $userAgent
        ]);
    } catch (PDOException $e2) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to log call: ' . $e2->getMessage()]);
        exit();
    }
}

echo json_encode(['success' => true]);
?>
