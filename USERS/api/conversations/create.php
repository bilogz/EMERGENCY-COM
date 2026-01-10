<?php
/**
 * Conversations Create (Adapter for Mobile App)
 * Creates or returns an active conversation for the given user.
 * Response shape matches the app's ConversationResponse.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../api/db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'conversation' => null]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $alertId = isset($input['alert_id']) ? (int)$input['alert_id'] : 0;
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id is required', 'conversation' => null]);
        exit;
    }

    // Try to find an active conversation for this user
    $stmt = $pdo->prepare("
        SELECT conversation_id, created_at 
        FROM conversations 
        WHERE user_id = ? AND status = 'active' 
        ORDER BY updated_at DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $conv = $stmt->fetch();

    if ($conv) {
        $conversationId = (int)$conv['conversation_id'];
        $createdAt = $conv['created_at'];
    } else {
        // Create a new conversation
        $stmt = $pdo->prepare("
            INSERT INTO conversations (user_id, user_name, status, created_at, updated_at) 
            VALUES (?, NULL, 'active', NOW(), NOW())
        ");
        $stmt->execute([$userId]);
        $conversationId = (int)$pdo->lastInsertId();

        // Fetch created_at for response
        $stmt = $pdo->prepare("SELECT created_at FROM conversations WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $row = $stmt->fetch();
        $createdAt = $row ? $row['created_at'] : date('Y-m-d H:i:s');
    }

    // Adapter response to match the app
    echo json_encode([
        'success' => true,
        'message' => 'Conversation ready',
        'conversation' => [
            'id' => $conversationId,
            'alert_id' => $alertId,          // Not stored; echoed back for app context
            'created_by' => $userId,
            'created_at' => $createdAt
        ]
    ]);
} catch (Throwable $e) {
    error_log('conversations/create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create conversation',
        'conversation' => null
    ]);
}



