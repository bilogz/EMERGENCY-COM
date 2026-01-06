<?php
/**
 * Close Conversation API
 * Allows users to close their conversation
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

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
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    
    if (empty($conversationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
        exit;
    }
    
    // Update conversation status to 'closed'
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET status = 'closed', updated_at = NOW() 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversationId]);
    
    // Also update chat queue if exists
    try {
        $stmt = $pdo->prepare("
            UPDATE chat_queue 
            SET status = 'closed', updated_at = NOW() 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$conversationId]);
    } catch (PDOException $e) {
        // Queue update is optional
        error_log('Chat queue update warning: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conversation closed successfully'
    ]);
    
} catch (PDOException $e) {
    error_log('Chat close error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to close conversation']);
}



