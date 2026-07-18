<?php
/**
 * Save Completed Call to Active List API
 * Adds completed emergency calls to the active conversations list for persistence
 */

session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Use existing database connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}
if (file_exists(__DIR__ . '/chat-logic.php')) {
    require_once __DIR__ . '/chat-logic.php';
}

if (!isset($pdo) || $pdo === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$callId = $input['callId'] ?? null;
$userId = $input['userId'] ?? null;
$userName = $input['userName'] ?? 'Unknown User';
$userPhone = $input['userPhone'] ?? null;
$userLocation = trim((string)($input['userLocation'] ?? ($input['location']['address'] ?? '')));
$duration = $input['duration'] ?? null;
$endedAt = $input['endedAt'] ?? time();
$event = strtolower(trim((string)($input['event'] ?? 'ended')));
$providedConversationId = $input['conversationId'] ?? null; // Use provided conversation ID if available
$emergencyType = trim((string)($input['emergencyType'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$providedPriority = is_array($input['incidentPriority'] ?? null) ? $input['incidentPriority'] : [];

function saveCallIncidentPriority(PDO $pdo, int $conversationId, array $priority): void {
    if ($conversationId <= 0 || empty($priority) || !function_exists('twc_ensure_incident_priority_columns')) {
        return;
    }
    try {
        twc_ensure_incident_priority_columns($pdo);
        $stmt = $pdo->prepare("
            UPDATE conversations
            SET incident_priority_score = ?,
                incident_priority_level = ?,
                incident_priority_color = ?,
                incident_priority_breakdown = ?,
                incident_priority_manual = 0
            WHERE conversation_id = ?
        ");
        $stmt->execute([
            (int)($priority['score'] ?? 0),
            strtolower((string)($priority['priority'] ?? $priority['level'] ?? 'low')),
            strtolower((string)($priority['color'] ?? 'green')),
            json_encode($priority['breakdown'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $conversationId,
        ]);
    } catch (Throwable $e) {
        error_log('Call priority save warning: ' . $e->getMessage());
    }
}

function saveCallColumnInfo(PDO $pdo, string $table, string $column): ?array {
    $allowed = ['conversations' => ['conversation_id'], 'chat_messages' => ['id', 'message_id']];
    if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) return null;
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column));
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    return is_array($row) ? $row : null;
}

function saveCallNeedsExplicitId(PDO $pdo, string $table, string $column): bool {
    $info = saveCallColumnInfo($pdo, $table, $column);
    return $info !== null && stripos((string)($info['Extra'] ?? ''), 'auto_increment') === false;
}

function saveCallNextId(PDO $pdo, string $table, string $column): int {
    $allowed = ['conversations' => ['conversation_id'], 'chat_messages' => ['id', 'message_id']];
    if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
        throw new InvalidArgumentException('Unsupported ID column.');
    }
    $stmt = $pdo->query("SELECT COALESCE(MAX(`{$column}`), 0) + 1 FROM `{$table}`");
    return max(1, (int)($stmt ? $stmt->fetchColumn() : 1));
}

function saveCallWithIdLock(PDO $pdo, string $lockName, callable $callback) {
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->execute([$lockName]);
    if ((int)$stmt->fetchColumn() !== 1) throw new RuntimeException('Could not reserve a database ID. Please retry.');
    try {
        return $callback();
    } finally {
        try {
            $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
        } catch (Throwable $e) {
            error_log('Save call ID lock release warning: ' . $e->getMessage());
        }
    }
}

function saveCallInsertConversation(
    PDO $pdo,
    $userId,
    string $userName,
    ?string $userPhone,
    ?string $userLocation,
    int $isGuest,
    string $status,
    string $lastMessage,
    int $timestamp,
    string $callId
): int {
    $insert = function (?int $explicitId = null) use ($pdo, $userId, $userName, $userPhone, $userLocation, $isGuest, $status, $lastMessage, $timestamp, $callId): int {
        $columns = ['user_id', 'user_name', 'user_phone', 'user_location', 'is_guest', 'status', 'last_message', 'last_message_time', 'created_at', 'updated_at'];
        $marks = ['?', '?', '?', '?', '?', '?', '?', 'FROM_UNIXTIME(?)', 'NOW()', 'NOW()'];
        $guestUserId = 'call_guest_' . substr(hash('sha256', $callId), 0, 24);
        $values = [$userId ?: $guestUserId, $userName, $userPhone, $userLocation, $isGuest, $status, $lastMessage, $timestamp];
        if ($explicitId !== null) {
            array_unshift($columns, 'conversation_id');
            array_unshift($marks, '?');
            array_unshift($values, $explicitId);
        }
        $stmt = $pdo->prepare('INSERT INTO conversations (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $marks) . ')');
        $stmt->execute($values);
        return $explicitId ?? (int)$pdo->lastInsertId();
    };

    if (!saveCallNeedsExplicitId($pdo, 'conversations', 'conversation_id')) return $insert();
    return saveCallWithIdLock($pdo, 'alertaraqc_conversations_id', function () use ($pdo, $insert): int {
        return $insert(saveCallNextId($pdo, 'conversations', 'conversation_id'));
    });
}

function saveCallMessageIdColumn(PDO $pdo): ?string {
    if (saveCallColumnInfo($pdo, 'chat_messages', 'message_id')) return 'message_id';
    if (saveCallColumnInfo($pdo, 'chat_messages', 'id')) return 'id';
    return null;
}

function saveCallInsertMessage(
    PDO $pdo,
    int $conversationId,
    string $senderId,
    string $senderName,
    string $senderType,
    string $message,
    int $isRead,
    int $timestamp
): int {
    $idColumn = saveCallMessageIdColumn($pdo);
    $insert = function (?int $explicitId = null) use ($pdo, $idColumn, $conversationId, $senderId, $senderName, $senderType, $message, $isRead, $timestamp): int {
        $columns = ['conversation_id', 'sender_id', 'sender_name', 'sender_type', 'message_text', 'is_read', 'created_at'];
        $marks = ['?', '?', '?', '?', '?', '?', 'FROM_UNIXTIME(?)'];
        $values = [$conversationId, $senderId, $senderName, $senderType === 'admin' ? 'admin' : 'user', $message, $isRead, $timestamp];
        if ($explicitId !== null && $idColumn !== null) {
            array_unshift($columns, $idColumn);
            array_unshift($marks, '?');
            array_unshift($values, $explicitId);
        }
        $stmt = $pdo->prepare('INSERT INTO chat_messages (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $marks) . ')');
        $stmt->execute($values);
        return $explicitId ?? (int)$pdo->lastInsertId();
    };

    if ($idColumn === null || !saveCallNeedsExplicitId($pdo, 'chat_messages', $idColumn)) return $insert();
    return saveCallWithIdLock($pdo, 'alertaraqc_chat_messages_id', function () use ($pdo, $idColumn, $insert): int {
        return $insert(saveCallNextId($pdo, 'chat_messages', $idColumn));
    });
}

if (!$callId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Call ID is required']);
    exit();
}

$incidentPriority = [];
if (!empty($providedPriority) && isset($providedPriority['score'])) {
    $incidentPriority = $providedPriority;
    $incidentPriority['priority'] = strtolower((string)($incidentPriority['priority'] ?? $incidentPriority['level'] ?? 'low'));
    if (function_exists('twc_incident_priority_config')) {
        $meta = twc_incident_priority_config()[$incidentPriority['priority']] ?? null;
        if ($meta) {
            $incidentPriority['label'] = $incidentPriority['label'] ?? $meta['label'];
            $incidentPriority['color'] = $incidentPriority['color'] ?? $meta['color'];
            $incidentPriority['hex'] = $incidentPriority['hex'] ?? $meta['hex'];
        }
    }
} elseif (function_exists('twc_calculate_incident_priority')) {
    $incidentPriority = twc_calculate_incident_priority([
        'incident_type' => $emergencyType,
        'description' => $description,
        'message' => $description,
        'last_message' => $description,
    ]);
}

try {
    $activeStatus = function_exists('twc_status_for_db') ? twc_status_for_db($pdo, 'open') : 'active';
    // Check if completed_calls table exists and if this call is already saved
    $existing = null;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'completed_calls'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id, conversation_id FROM completed_calls WHERE call_id = ?");
            $stmt->execute([$callId]);
            $existing = $stmt->fetch();
        }
    } catch (PDOException $e) {
        // Table doesn't exist, continue without it
        error_log('completed_calls table check: ' . $e->getMessage());
    }
    
    if ($existing) {
        // Call already saved, but we should still add the "Call ended" message if it doesn't exist
        $conversationId = $existing['conversation_id'];
        
        // Check if "Call ended" message already exists for this conversation
        $messageIdColumn = saveCallMessageIdColumn($pdo) ?: 'message_id';
        $stmt = $pdo->prepare("SELECT `{$messageIdColumn}` FROM chat_messages WHERE conversation_id = ? AND message_text LIKE '[CALL_ENDED]%'");
        $stmt->execute([$conversationId]);
        $callEndedMsg = $stmt->fetch();
        
        if (!$callEndedMsg) {
            // Add "Call ended" system message
            $durationStr = $duration ? gmdate('H:i:s', $duration) : 'N/A';
            $callEndedMessage = "[CALL_ENDED]Call ended • Duration: " . $durationStr;
            
            saveCallInsertMessage($pdo, (int)$conversationId, 'system', 'System', 'user', $callEndedMessage, 1, (int)$endedAt);
        }

        if ($description !== '') {
            $stmt = $pdo->prepare("SELECT `{$messageIdColumn}` FROM chat_messages WHERE conversation_id = ? AND message_text LIKE '[CALL_CONTEXT]%' LIMIT 1");
            $stmt->execute([$conversationId]);
            if (!$stmt->fetch()) {
                $contextMessage = '[CALL_CONTEXT]' . $description;
                saveCallInsertMessage($pdo, (int)$conversationId, 'admin', $_SESSION['admin_username'] ?? 'Administrator', 'admin', $contextMessage, 0, (int)$endedAt);
            }
        }
        saveCallIncidentPriority($pdo, (int)$conversationId, $incidentPriority);
        
        echo json_encode(['success' => true, 'message' => 'Call already saved', 'conversationId' => (int)$conversationId]);
        exit;
    }
    
    // Use provided conversation ID if available, otherwise find or create one
    $conversationId = $providedConversationId;
    
    // If no provided conversation ID, try to find existing conversation for this user
    if (!$conversationId) {
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT conversation_id FROM conversations 
                WHERE user_id = ? 
                ORDER BY updated_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $existingConv = $stmt->fetch();
            if ($existingConv) {
                $conversationId = $existingConv['conversation_id'];
            }
        }
        
        // Also try by phone number if no user_id match
        if (!$conversationId && $userPhone) {
            $stmt = $pdo->prepare("
                SELECT conversation_id FROM conversations 
                WHERE user_phone = ? 
                ORDER BY updated_at DESC LIMIT 1
            ");
            $stmt->execute([$userPhone]);
            $existingConv = $stmt->fetch();
            if ($existingConv) {
                $conversationId = $existingConv['conversation_id'];
            }
        }

        if (!$conversationId && $userLocation !== '') {
            $stmt = $pdo->prepare("
                SELECT conversation_id FROM conversations
                WHERE user_location = ?
                ORDER BY updated_at DESC LIMIT 1
            ");
            $stmt->execute([$userLocation]);
            $existingConv = $stmt->fetch();
            if ($existingConv) {
                $conversationId = $existingConv['conversation_id'];
            }
        }
    }
    
    // If still no conversation, create a new one
    if (!$conversationId) {
        // Build last message text
        $lastMessage = $event === 'declined'
            ? 'Emergency call declined by admin'
            : ($event === 'transferred'
                ? '[TRANSFERRED] Emergency call transferred to response team'
                : "Emergency call completed (Duration: " . ($duration ? gmdate('H:i:s', $duration) : 'N/A') . ")");
        
        // Determine if user is guest (no user_id means guest)
        $isGuest = empty($userId) ? 1 : 0;
        
        $conversationId = saveCallInsertConversation(
            $pdo,
            $userId,
            (string)$userName,
            $userPhone ? (string)$userPhone : null,
            $userLocation !== '' ? $userLocation : null,
            $isGuest,
            $activeStatus,
            $lastMessage,
            (int)$endedAt,
            (string)$callId
        );
    } else {
        // Update existing conversation with call ended/declined message
        $lastMessage = $event === 'declined'
            ? 'Emergency call declined by admin'
            : ($event === 'transferred'
                ? '[TRANSFERRED] Emergency call transferred to response team'
                : "Emergency call completed (Duration: " . ($duration ? gmdate('H:i:s', $duration) : 'N/A') . ")");
        
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET last_message = ?, 
                last_message_time = FROM_UNIXTIME(?), 
                updated_at = NOW(),
                status = ?
            WHERE conversation_id = ?
        ");
        $stmt->execute([$lastMessage, $endedAt, $activeStatus, $conversationId]);
    }
    
    // Add "Call ended" system message to the conversation
    $durationStr = $duration ? gmdate('H:i:s', $duration) : 'N/A';
    $adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';
    $callEndedMessage = $event === 'declined'
        ? "[CALL_DECLINED]Call declined by {$adminName}"
        : ($event === 'transferred'
            ? "[CALL_TRANSFERRED]Call transferred to response team by {$adminName}"
            : "[CALL_ENDED]Call ended - Duration: " . $durationStr);
    
    saveCallInsertMessage($pdo, (int)$conversationId, 'system', $userName ?: 'Emergency Call User', 'user', $callEndedMessage, 0, (int)$endedAt);

    if ($description !== '') {
        $contextMessage = '[CALL_CONTEXT]' . $description;
        saveCallInsertMessage($pdo, (int)$conversationId, 'admin', $adminName, 'admin', $contextMessage, 0, (int)$endedAt);
    }
    
    // Update conversation's last message
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message = ?, last_message_time = FROM_UNIXTIME(?), updated_at = NOW(), status = 'open'
        WHERE conversation_id = ?
    ");
    $stmt->execute([$callEndedMessage, $endedAt, $conversationId]);
    saveCallIncidentPriority($pdo, (int)$conversationId, $incidentPriority);
    
    // Also save to completed_calls table for reference (if table exists)
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'completed_calls'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO completed_calls (call_id, conversation_id, user_id, user_name, duration, ended_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$callId, $conversationId, $userId, $userName, $duration, $endedAt]);
        }
    } catch (PDOException $e) {
        // Table doesn't exist or insert failed, but that's okay - we've already saved to conversations
        error_log('completed_calls insert note: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'conversationId' => $conversationId,
        'message' => 'Call saved to active list'
    ]);
    
} catch (PDOException $e) {
    error_log('Save completed call error: ' . $e->getMessage());
    error_log('SQL Error Info: ' . print_r($e->errorInfo, true));
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error',
        'error' => $e->getMessage() // Include error for debugging
    ]);
} catch (Exception $e) {
    error_log('Save completed call general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to save call',
        'error' => $e->getMessage()
    ]);
}
?>
