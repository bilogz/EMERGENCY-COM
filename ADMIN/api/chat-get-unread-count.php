<?php
/**
 * Get Unread Chat Count API (Admin)
 * Counts unread conversations across active workflow states.
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

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    if (!twc_chat_storage_available($pdo)) {
        echo json_encode([
            'success' => true,
            'unreadCount' => 0,
            'warning' => 'Chat storage tables are unavailable in this local database.',
        ]);
        exit;
    }

    $activeStatuses = twc_active_statuses();
    $sql = "
        SELECT COUNT(DISTINCT c.conversation_id) AS unread_conversations
        FROM conversations c
        JOIN chat_messages m ON c.conversation_id = m.conversation_id
        WHERE c.status IN (" . twc_placeholders($activeStatuses) . ")
          AND m.is_read = 0
          AND m.sender_type <> 'admin'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($activeStatuses);
    $unreadCount = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'unreadCount' => $unreadCount,
    ]);
} catch (PDOException $e) {
    $message = $e->getMessage();
    if (stripos($message, "doesn't exist in engine") !== false || stripos($message, 'Base table or view not found') !== false) {
        echo json_encode([
            'success' => true,
            'unreadCount' => 0,
            'warning' => 'Chat tables are missing or corrupted in this local database.',
        ]);
        exit;
    }
    error_log('Admin chat get unread count error: ' . $message);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get unread count']);
}
