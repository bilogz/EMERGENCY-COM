<?php
/**
 * Get Unread Chat Count API (Admin)
 * Returns the count of unread active conversations/messages
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
    // Count unread messages from active conversations
    // Assuming 'is_read' = 0 and sender_type != 'admin' (i.e. citizen or user)
    // And conversation status is 'active'
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT c.conversation_id) as unread_conversations
        FROM conversations c
        JOIN chat_messages m ON c.conversation_id = m.conversation_id
        WHERE c.status = 'active'
        AND m.is_read = 0
        AND m.sender_type != 'admin'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = (int)$result['unread_conversations'];
    
    echo json_encode([
        'success' => true,
        'unreadCount' => $unreadCount
    ]);
    
} catch (PDOException $e) {
    error_log('Admin chat get unread count error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get unread count']);
}
