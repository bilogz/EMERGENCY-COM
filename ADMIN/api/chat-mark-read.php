<?php
/**
 * Mark Conversation Read API (Admin)
 * Marks inbound (non-admin) messages as read for a conversation.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/chat-logic.php';

session_start();

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
    $conversationId = (int)($input['conversationId'] ?? $_POST['conversationId'] ?? 0);

    if ($conversationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
        exit;
    }

    $hasReadAt = twc_column_exists($pdo, 'chat_messages', 'read_at');
    $sql = "UPDATE chat_messages SET is_read = 1";
    if ($hasReadAt) {
        $sql .= ", read_at = NOW()";
    }
    $sql .= " WHERE conversation_id = ? AND sender_type <> 'admin' AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conversationId]);
    $updated = $stmt->rowCount();

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) AS unread_count
        FROM chat_messages
        WHERE conversation_id = ?
          AND sender_type <> 'admin'
          AND is_read = 0
    ");
    $countStmt->execute([$conversationId]);
    $unreadCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'conversationId' => $conversationId,
        'unreadCount' => $unreadCount,
    ]);
} catch (PDOException $e) {
    error_log('Admin chat mark read error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
}

