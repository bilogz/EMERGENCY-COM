<?php
/**
 * Close Conversation API
 * Allows users to close their conversation
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

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
    
    $statusClosed = twc_status_for_db($pdo, 'closed');
    $closedByLabel = 'Citizen/User';

    // Update conversation status and mark who closed it.
    $setParts = ["status = ?", "last_message = CONCAT('Closed by ', ?)", "updated_at = NOW()"];
    $params = [$statusClosed, $closedByLabel];

    // Store closed_by if schema supports it.
    if (twc_column_exists($pdo, 'conversations', 'closed_by')) {
        $setParts[] = "closed_by = ?";
        $params[] = $closedByLabel;
    }

    $sql = "UPDATE conversations SET " . implode(', ', $setParts) . " WHERE conversation_id = ?";
    $params[] = $conversationId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // If conversation does not exist, return explicit error.
    if ($stmt->rowCount() === 0) {
        $existsStmt = $pdo->prepare("SELECT conversation_id FROM conversations WHERE conversation_id = ? LIMIT 1");
        $existsStmt->execute([$conversationId]);
        if (!$existsStmt->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            exit;
        }
    }
    
    // Also update chat queue if exists.
    try {
        $queueStatus = twc_status_for_db($pdo, 'closed');
        $stmt = $pdo->prepare("
            UPDATE chat_queue 
            SET status = ?, updated_at = NOW() 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$queueStatus, $conversationId]);
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





