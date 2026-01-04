<?php
/**
 * Get Chat Messages API (Admin)
 * Retrieves messages for a conversation
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conversationId = $_GET['conversationId'] ?? null;
    $lastMessageId = $_GET['lastMessageId'] ?? 0;
    
    if (empty($conversationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'conversationId is required']);
        exit;
    }
    
    // Get messages
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
    
    // Mark messages as read
    if (!empty($messages)) {
        $messageIds = array_column($messages, 'message_id');
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE message_id IN ($placeholders)
        ");
        $stmt->execute($messageIds);
    }
    
    // Format messages
    $formattedMessages = array_map(function($msg) {
        return [
            'id' => $msg['message_id'],
            'conversationId' => $msg['conversation_id'],
            'senderId' => $msg['sender_id'],
            'senderName' => $msg['sender_name'],
            'senderType' => $msg['sender_type'],
            'text' => $msg['message_text'],
            'timestamp' => strtotime($msg['created_at']) * 1000,
            'read' => (bool)$msg['is_read']
        ];
    }, $messages);
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages
    ]);
    
} catch (PDOException $e) {
    error_log('Admin chat get messages error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve messages']);
}

