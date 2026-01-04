<?php
/**
 * Send Chat Message API (Admin)
 * Handles sending messages from admin to users
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $text = $input['text'] ?? $_POST['text'] ?? '';
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 'admin';
    $adminName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin';
    
    if (empty($text)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message text is required']);
        exit;
    }
    
    if (empty($conversationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages 
        (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
        VALUES (?, ?, ?, 'admin', ?, 0, NOW())
    ");
    $stmt->execute([
        $conversationId,
        $adminId,
        $adminName,
        $text
    ]);
    $messageId = $pdo->lastInsertId();
    
    // Update conversation
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message = ?, last_message_time = NOW(), updated_at = NOW(), assigned_to = ?
        WHERE conversation_id = ?
    ");
    $stmt->execute([$text, $adminId, $conversationId]);
    
    // Update chat queue status
    $stmt = $pdo->prepare("
        UPDATE chat_queue 
        SET status = 'accepted', assigned_to = ?, updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $stmt->execute([$adminId, $conversationId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'messageId' => $messageId,
        'message' => 'Message sent successfully'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

