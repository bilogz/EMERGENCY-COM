<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';
/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    apiResponse::error('Database connection is not available.', 500);
}

function normalize_json_field($value, string $fieldName): ?string {
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $normalized = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            if ($normalized === false) {
                apiResponse::error("Invalid {$fieldName} JSON.", 400);
            }
            return $normalized;
        }

        return $trimmed;
    }

    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        apiResponse::error("Invalid {$fieldName} JSON.", 400);
    }
    return $encoded;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    apiResponse::error('Invalid request method. Use POST.', 405);
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    // Fallback for form-encoded clients
    $payload = $_POST;
}

$callId = trim((string)($payload['call_id'] ?? ''));
$userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
$role = trim((string)($payload['role'] ?? 'user'));
$event = trim((string)($payload['event'] ?? ''));
$timestamp = isset($payload['timestamp']) ? (int)$payload['timestamp'] : time();
$durationSec = isset($payload['duration_sec']) ? (int)$payload['duration_sec'] : null;
$room = isset($payload['room']) ? trim((string)$payload['room']) : null;
$locationData = $payload['location_data'] ?? null;
$metadata = $payload['metadata'] ?? null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

if ($callId === '' || $userId <= 0 || $event === '') {
    apiResponse::error('call_id, user_id and event are required.', 400);
}

$allowedRoles = ['user', 'admin'];
if (!in_array(strtolower($role), $allowedRoles, true)) {
    apiResponse::error('Invalid role. Allowed: user, admin.', 400);
}
$role = strtolower($role);

$allowedEvents = ['started', 'incoming', 'connected', 'ended', 'cancelled', 'declined', 'missed'];
if (!in_array(strtolower($event), $allowedEvents, true)) {
    apiResponse::error('Invalid event.', 400);
}
$event = strtolower($event);

if ($durationSec !== null && $durationSec < 0) {
    $durationSec = 0;
}

if ($timestamp <= 0) {
    $timestamp = time();
}

$locationDataJson = normalize_json_field($locationData, 'location_data');
$metadataJson = normalize_json_field($metadata, 'metadata');

try {
    $stmt = $pdo->prepare("
        INSERT INTO call_logs (
            call_id,
            user_id,
            role,
            event,
            timestamp,
            duration_sec,
            location_data,
            room,
            metadata,
            ip_address,
            user_agent
        ) VALUES (
            :call_id,
            :user_id,
            :role,
            :event,
            :timestamp,
            :duration_sec,
            :location_data,
            :room,
            :metadata,
            :ip_address,
            :user_agent
        )
    ");

    $stmt->bindValue(':call_id', $callId, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':role', $role, PDO::PARAM_STR);
    $stmt->bindValue(':event', $event, PDO::PARAM_STR);
    $stmt->bindValue(':timestamp', $timestamp, PDO::PARAM_INT);
    if ($durationSec === null) {
        $stmt->bindValue(':duration_sec', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':duration_sec', $durationSec, PDO::PARAM_INT);
    }
    if ($locationDataJson === null) {
        $stmt->bindValue(':location_data', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':location_data', $locationDataJson, PDO::PARAM_STR);
    }
    if ($room === null || $room === '') {
        $stmt->bindValue(':room', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':room', $room, PDO::PARAM_STR);
    }
    if ($metadataJson === null) {
        $stmt->bindValue(':metadata', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':metadata', $metadataJson, PDO::PARAM_STR);
    }
    if ($ipAddress === null || $ipAddress === '') {
        $stmt->bindValue(':ip_address', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
    }
    if ($userAgent === null || $userAgent === '') {
        $stmt->bindValue(':user_agent', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
    }

    $stmt->execute();
    $insertId = (int)$pdo->lastInsertId();

    apiResponse::success([
        'id' => $insertId,
        'call_id' => $callId,
        'event' => $event
    ], 'Call event logged successfully.');

} catch (PDOException $e) {
    error_log('Call Event DB Error: ' . $e->getMessage());
    apiResponse::error('Database insert failed.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Call Event Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}

