<?php
/**
 * Send Chat Message API (Admin)
 * Staff/admin reply flow: updates thread to waiting_user.
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
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    $text = trim((string)($input['text'] ?? $_POST['text'] ?? ''));
    $conversationId = (int)($input['conversationId'] ?? $_POST['conversationId'] ?? 0);
    $rawCategory = $input['category'] ?? $_POST['category'] ?? null;
    $rawPriority = $input['priority'] ?? $_POST['priority'] ?? null;

    $adminIdRaw = $_SESSION['admin_user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    $adminId = twc_safe_int($adminIdRaw);
    $adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin';

    if ($text === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message text is required']);
        exit;
    }
    if ($conversationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID is required']);
        exit;
    }

    $convStmt = $pdo->prepare("
        SELECT conversation_id, status, user_concern
        FROM conversations
        WHERE conversation_id = ?
        LIMIT 1
    ");
    $convStmt->execute([$conversationId]);
    $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    if (twc_is_closed_status($conversation['status'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'This conversation is closed.']);
        exit;
    }

    $category = twc_normalize_category($rawCategory ?? $conversation['user_concern'] ?? '');
    $priority = twc_normalize_priority($rawPriority ?? '', $text, $category);
    $targetStatus = twc_status_for_db($pdo, 'waiting_user');

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO chat_messages
        (conversation_id, sender_id, sender_name, sender_type, message_text, is_read, created_at)
        VALUES (?, ?, ?, 'admin', ?, 0, NOW())
    ");
    $insertStmt->execute([
        $conversationId,
        (string)($adminIdRaw ?? 'admin'),
        $adminName,
        $text,
    ]);
    $messageId = (int)$pdo->lastInsertId();

    $updateParts = [
        "last_message = ?",
        "last_message_time = NOW()",
        "updated_at = NOW()",
        "status = ?",
    ];
    $updateParams = [$text, $targetStatus];

    if ($adminId !== null) {
        $updateParts[] = "assigned_to = ?";
        $updateParams[] = $adminId;
    }
    if (twc_column_exists($pdo, 'conversations', 'category') && $category !== '') {
        $updateParts[] = "category = ?";
        $updateParams[] = $category;
    }
    if (twc_column_exists($pdo, 'conversations', 'priority')) {
        $updateParts[] = "priority = ?";
        $updateParams[] = $priority;
    }
    if ($category !== '') {
        $updateParts[] = "user_concern = ?";
        $updateParams[] = $category;
    }

    $updateParams[] = $conversationId;
    $updateSql = "UPDATE conversations SET " . implode(', ', $updateParts) . " WHERE conversation_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);

    if (twc_table_exists($pdo, 'chat_queue')) {
        try {
            $queueSql = "UPDATE chat_queue SET status = 'accepted', updated_at = NOW()";
            $queueParams = [];
            if ($adminId !== null && twc_column_exists($pdo, 'chat_queue', 'assigned_to')) {
                $queueSql .= ", assigned_to = ?";
                $queueParams[] = $adminId;
            }
            $queueSql .= " WHERE conversation_id = ?";
            $queueParams[] = $conversationId;
            $queueStmt = $pdo->prepare($queueSql);
            $queueStmt->execute($queueParams);
        } catch (Throwable $e) {
            error_log('Chat queue update warning: ' . $e->getMessage());
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'messageId' => $messageId,
        'conversationId' => $conversationId,
        'workflowStatus' => $targetStatus,
        'message' => 'Message sent successfully',
    ]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message',
        'error' => $e->getMessage(),
    ]);
}
