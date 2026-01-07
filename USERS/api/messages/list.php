<?php
/**
 * Messages List (Adapter for Mobile App)
 * Accepts: conversation_id, last_message_id (optional)
 * Returns MessagesResponse with fields expected by the app.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../api/db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Database connection failed']);
    exit;
}

try {
    $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
    $lastMessageId = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

    if ($conversationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'messages' => [], 'error' => 'conversation_id is required']);
        exit;
    }

    if ($lastMessageId > 0) {
        $stmt = $pdo->prepare("
            SELECT message_id, conversation_id, sender_id, sender_name, message_text, created_at
            FROM chat_messages
            WHERE conversation_id = ? AND message_id > ?
            ORDER BY message_id ASC
        ");
        $stmt->execute([$conversationId, $lastMessageId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT message_id, conversation_id, sender_id, sender_name, message_text, created_at
            FROM chat_messages
            WHERE conversation_id = ?
            ORDER BY message_id ASC
        ");
        $stmt->execute([$conversationId]);
    }
    $rows = $stmt->fetchAll();

    // Map to app's expected fields
    $messages = array_map(function ($m) {
        return [
            'id' => (int)$m['message_id'],
            'conversation_id' => (int)$m['conversation_id'],
            'sender_id' => (int)$m['sender_id'],
            'senderName' => $m['sender_name'] ?? null,
            'messageText' => $m['message_text'] ?? '',
            'sent_at' => $m['created_at'],
            'icon' => null,
            'nonce' => null
        ];
    }, $rows);

    echo json_encode(['success' => true, 'messages' => $messages, 'error' => null]);
} catch (Throwable $e) {
    error_log('messages/list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Failed to fetch messages']);
}


