<?php
/**
 * Close Conversation API (Admin)
 * Allows admins to close conversations
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
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    
    if (empty($conversationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
        exit;
    }
    
    // Get admin name
    $adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';
    
    // Update conversation status to 'closed' and store who closed it
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET status = 'closed', 
            last_message = CONCAT('Closed by ', ?),
            updated_at = NOW() 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$adminName, $conversationId]);
    
    // Store who closed the conversation in a separate field if available
    // Check if closed_by column exists, if not, we'll use last_message
    try {
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET closed_by = ?, updated_at = NOW() 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$adminName, $conversationId]);
    } catch (PDOException $e) {
        // Column might not exist, that's okay - we stored it in last_message
        error_log('closed_by column might not exist: ' . $e->getMessage());
    }
    
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
    error_log('Admin chat close error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to close conversation']);
}

