<?php
/**
 * UNIFIED EMERGENCY CALLS LOG ENDPOINT
 * 
 * GET: Retrieve list of emergency call logs.
 * POST: Create/log an emergency call event or dispatcher event.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/auth.php';

// Helper: Ensure call_logs table exists
function checkCallLogsTable(PDO $pdo): bool {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS call_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                call_id VARCHAR(100) NOT NULL COMMENT 'Unique call identifier',
                user_id INT DEFAULT NULL COMMENT 'User who initiated the call',
                role VARCHAR(20) NOT NULL COMMENT 'user, admin, dispatcher, external',
                event VARCHAR(50) NOT NULL COMMENT 'started, connected, ended, cancelled, declined, accepted',
                timestamp BIGINT NOT NULL COMMENT 'Unix timestamp of event',
                duration_sec INT DEFAULT NULL COMMENT 'Call duration in seconds',
                location_data JSON DEFAULT NULL COMMENT 'Coordinates at time of call',
                room VARCHAR(128) DEFAULT NULL COMMENT 'Signal room identifier',
                metadata JSON DEFAULT NULL COMMENT 'Additional meta-attributes',
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_call_id (call_id),
                INDEX idx_user_id (user_id),
                INDEX idx_role (role),
                INDEX idx_event (event),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (PDOException $e) {
        error_log('Call logs table check note: ' . $e->getMessage());
        return false;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
checkCallLogsTable($pdo);

// Handle GET calls retrieval
if ($method === 'GET') {
    try {
        $event = isset($_GET['event']) && $_GET['event'] !== '' ? trim($_GET['event']) : null;
        $role = isset($_GET['role']) && $_GET['role'] !== '' ? trim($_GET['role']) : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;

        $query = "SELECT * FROM call_logs WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM call_logs WHERE 1=1";
        $params = [];

        if ($event) {
            $query .= " AND event = ?";
            $countQuery .= " AND event = ?";
            $params[] = $event;
        }
        if ($role) {
            $query .= " AND role = ?";
            $countQuery .= " AND role = ?";
            $params[] = $role;
        }

        // Fetch Count
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        // Fetch Data
        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($query);
        
        $bindIdx = 1;
        foreach ($params as $paramVal) {
            $stmt->bindValue($bindIdx++, $paramVal, PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIdx++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($bindIdx++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON columns for presentation
        foreach ($logs as &$log) {
            if (isset($log['location_data'])) {
                $log['location_data'] = json_decode($log['location_data'], true);
            }
            if (isset($log['metadata'])) {
                $log['metadata'] = json_decode($log['metadata'], true);
            }
        }

        $totalPages = ceil($totalRecords / $limit);

        logApiAccess($pdo, $deptName, '/api/calls.php', 'GET', 200, "Listed " . count($logs) . " call logs");
        sendJsonResponse(true, 'Call logs retrieved successfully.', [
            'call_logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages
            ]
        ]);
        
    } catch (PDOException $e) {
        logApiAccess($pdo, $deptName, '/api/calls.php', 'GET', 500, "Database error: " . $e->getMessage());
        sendJsonResponse(false, 'Database query failed: ' . $e->getMessage(), [], 500);
    }
}

// Handle POST call logging
elseif ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $callId = trim($input['call_id'] ?? $input['callId'] ?? '');
        $userId = isset($input['user_id']) && $input['user_id'] !== '' ? (int)$input['user_id'] : null;
        $role = trim($input['role'] ?? 'external');
        $event = trim($input['event'] ?? '');
        $durationSec = isset($input['duration_sec']) || isset($input['durationSec']) ? (int)($input['duration_sec'] ?? $input['durationSec']) : null;
        
        $location = $input['location'] ?? $input['location_data'] ?? null;
        $metadata = $input['metadata'] ?? null;
        $room = trim($input['room'] ?? '');

        if (empty($callId) || empty($event)) {
            sendJsonResponse(false, 'Bad Request: call_id and event fields are required.', [], 400);
        }

        // Prepare JSON values
        $locationJson = null;
        if ($location !== null) {
            $locationJson = is_string($location) ? $location : json_encode($location);
        }
        
        $metaArr = is_array($metadata) ? $metadata : [];
        $metaArr['logged_by_api_department'] = $deptName;
        $metadataJson = json_encode($metaArr);

        $timestamp = time();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO call_logs 
            (call_id, user_id, role, event, timestamp, duration_sec, location_data, room, metadata, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $callId,
            $userId,
            $role,
            $event,
            $timestamp,
            $durationSec,
            $locationJson,
            empty($room) ? null : $room,
            $metadataJson,
            $ipAddress,
            $userAgent
        ]);
        
        $logId = $pdo->lastInsertId();

        logApiAccess($pdo, $deptName, '/api/calls.php', 'POST', 201, "Logged call event '$event' for call_id '$callId'");
        sendJsonResponse(true, 'Call event logged successfully.', [
            'log_id' => $logId,
            'call_id' => $callId,
            'event' => $event
        ], 201);

    } catch (PDOException $e) {
        logApiAccess($pdo, $deptName, '/api/calls.php', 'POST', 500, "Database query exception: " . $e->getMessage());
        sendJsonResponse(false, 'Database insert failed: ' . $e->getMessage(), [], 500);
    }
} else {
    sendJsonResponse(false, 'Method Not Allowed.', [], 405);
}
