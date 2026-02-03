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
$duration = $input['duration'] ?? null;
$endedAt = $input['endedAt'] ?? time();
$providedConversationId = $input['conversationId'] ?? null; // Use provided conversation ID if available

if (!$callId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Call ID is required']);
    exit();
}

try {
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
        $stmt = $pdo->prepare("SELECT id FROM chat_messages WHERE conversation_id = ? AND message_text LIKE '[CALL_ENDED]%'");
        $stmt->execute([$conversationId]);
        $callEndedMsg = $stmt->fetch();
        
        if (!$callEndedMsg) {
            // Add "Call ended" system message
            $durationStr = $duration ? gmdate('H:i:s', $duration) : 'N/A';
            $callEndedMessage = "[CALL_ENDED]Call ended • Duration: " . $durationStr;
            
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages 
                (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
                VALUES (?, 'system', 'System', 'system', ?, 1, FROM_UNIXTIME(?))
            ");
            $stmt->execute([$conversationId, $callEndedMessage, $endedAt]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Call already saved']);
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
    }
    
    // If still no conversation, create a new one
    if (!$conversationId) {
        // Build last message text
        $lastMessage = "Emergency call completed (Duration: " . ($duration ? gmdate('H:i:s', $duration) : 'N/A') . ")";
        
        // Determine if user is guest (no user_id means guest)
        $isGuest = empty($userId) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO conversations (
                user_id, user_name, user_phone, is_guest, status, 
                last_message, last_message_time, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'closed', ?, FROM_UNIXTIME(?), NOW(), NOW())
        ");
        
        $stmt->execute([
            $userId ?: null,
            $userName,
            $userPhone ?: null,
            $isGuest,
            $lastMessage,
            $endedAt
        ]);
        
        $conversationId = $pdo->lastInsertId();
    } else {
        // Update existing conversation with call ended message
        $lastMessage = "Emergency call completed (Duration: " . ($duration ? gmdate('H:i:s', $duration) : 'N/A') . ")";
        
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET last_message = ?, 
                last_message_time = FROM_UNIXTIME(?), 
                updated_at = NOW(),
                status = 'closed'
            WHERE conversation_id = ?
        ");
        $stmt->execute([$lastMessage, $endedAt, $conversationId]);
    }
    
    // Add "Call ended" system message to the conversation
    $durationStr = $duration ? gmdate('H:i:s', $duration) : 'N/A';
    $callEndedMessage = "[CALL_ENDED]Call ended • Duration: " . $durationStr;
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages 
        (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
        VALUES (?, 'system', 'System', 'system', ?, 1, FROM_UNIXTIME(?))
    ");
    $stmt->execute([$conversationId, $callEndedMessage, $endedAt]);
    
    // Update conversation's last message
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message = ?, last_message_time = FROM_UNIXTIME(?), updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $stmt->execute([$callEndedMessage, $endedAt, $conversationId]);
    
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
