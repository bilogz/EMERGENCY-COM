<?php
/**
 * Get Chat Messages API
 * Retrieves messages for a conversation
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conversationId = $_GET['conversationId'] ?? null;
    $userId = $_GET['userId'] ?? null;
    $lastMessageId = $_GET['lastMessageId'] ?? 0;
    
    if (empty($conversationId) && empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'conversationId or userId is required']);
        exit;
    }
    
    // If userId provided but no conversationId, get the latest conversation
    if (empty($conversationId) && !empty($userId)) {
        $stmt = $pdo->prepare("
            SELECT conversation_id 
            FROM conversations 
            WHERE user_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $conv = $stmt->fetch();
        if ($conv) {
            $conversationId = $conv['conversation_id'];
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'conversationId' => null]);
            exit;
        }
    }
    
    // Get conversation status and verify it matches device/IP (for anonymous users)
    require_once __DIR__ . '/device_tracking.php';
    $ipAddress = getClientIP();
    $deviceInfo = formatDeviceInfoForDB();
    
    $stmt = $pdo->prepare("
        SELECT status, is_guest, ip_address, device_info 
        FROM conversations 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversationId]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // For anonymous users, verify device/IP matches
        if ($conversation['is_guest'] == 1) {
            if ($conversation['ip_address'] !== $ipAddress || $conversation['device_info'] !== $deviceInfo) {
                // Device/IP mismatch - conversation doesn't belong to this device
                echo json_encode([
                    'success' => false,
                    'message' => 'Conversation not found for this device',
                    'conversationStatus' => 'closed'
                ]);
                exit;
            }
        }
        $conversationStatus = $conversation['status'] ?? 'active';
    } else {
        $conversationStatus = 'closed';
    }
    
    // Get messages newer than lastMessageId
    $stmt = $pdo->prepare("
        SELECT 
            message_id,
            conversation_id,
            sender_id,
            sender_name,
            sender_type,
            message_text,
            created_at,
            is_read
        FROM chat_messages
        WHERE conversation_id = ? AND message_id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversationId, $lastMessageId]);
    $messages = $stmt->fetchAll();
    
    // Format messages
    $formattedMessages = array_map(function($msg) {
        return [
            'id' => $msg['message_id'],
            'conversationId' => $msg['conversation_id'],
            'senderId' => $msg['sender_id'],
            'senderName' => $msg['sender_name'],
            'senderType' => $msg['sender_type'],
            'text' => $msg['message_text'],
            'timestamp' => strtotime($msg['created_at']) * 1000, // Convert to milliseconds
            'read' => (bool)$msg['is_read']
        ];
    }, $messages);
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'conversationId' => $conversationId,
        'conversationStatus' => $conversationStatus
    ]);
    
} catch (PDOException $e) {
    error_log('Chat get messages error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve messages']);
}

