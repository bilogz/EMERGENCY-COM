<?php
/**
 * Realtime SSE endpoint (User/Citizen)
 * Emits:
 * - message:new (admin/staff replies)
 * - conversation:status_changed
 * - conversation:unread
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

if (!$pdo) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');
while (ob_get_level() > 0) {
    @ob_end_flush();
}
ob_implicit_flush(true);

function sse_emit_user($event, $data) {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    @flush();
}

$conversationId = isset($_GET['conversationId']) ? (int)$_GET['conversationId'] : 0;
$userId = $_GET['userId'] ?? null;
$lastMessageId = isset($_GET['lastMessageId']) ? (int)$_GET['lastMessageId'] : 0;

if ($conversationId <= 0 && empty($userId)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'conversationId or userId is required']);
    exit;
}

if ($conversationId <= 0 && !empty($userId)) {
    $active = twc_active_statuses();
    $stmt = $pdo->prepare("
        SELECT conversation_id
        FROM conversations
        WHERE user_id = ? AND status IN (" . twc_placeholders($active) . ")
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    $params = [$userId];
    $params = array_merge($params, $active);
    $stmt->execute($params);
    $conversationId = (int)$stmt->fetchColumn();
}

if ($conversationId <= 0) {
    sse_emit_user('ready', ['conversationId' => null, 'lastMessageId' => $lastMessageId, 'unreadCount' => 0]);
    sse_emit_user('end', ['reason' => 'no_conversation']);
    exit;
}

$statusStmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ? LIMIT 1");
$statusStmt->execute([$conversationId]);
$lastStatus = strtolower((string)$statusStmt->fetchColumn());

$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM chat_messages
    WHERE conversation_id = ?
      AND sender_type = 'admin'
      AND is_read = 0
");
$unreadStmt->execute([$conversationId]);
$lastUnread = (int)$unreadStmt->fetchColumn();

sse_emit_user('ready', [
    'conversationId' => $conversationId,
    'lastMessageId' => $lastMessageId,
    'unreadCount' => $lastUnread
]);

$maxLoops = 15; // ~30s @ 2s interval
for ($i = 0; $i < $maxLoops; $i++) {
    if (connection_aborted()) {
        break;
    }

    $msgStmt = $pdo->prepare("
        SELECT message_id, conversation_id, sender_id, sender_name, sender_type, message_text, created_at, is_read
        FROM chat_messages
        WHERE conversation_id = ? AND message_id > ?
        ORDER BY message_id ASC
        LIMIT 100
    ");
    $msgStmt->execute([$conversationId, $lastMessageId]);
    $rows = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $lastMessageId = max($lastMessageId, (int)$row['message_id']);
        sse_emit_user('message:new', [
            'id' => (int)$row['message_id'],
            'conversationId' => (int)$row['conversation_id'],
            'senderId' => $row['sender_id'],
            'senderRole' => $row['sender_type'] === 'admin' ? 'staff' : 'citizen',
            'senderName' => $row['sender_name'],
            'body' => $row['message_text'],
            'createdAt' => strtotime((string)$row['created_at']) * 1000,
            'read' => (bool)$row['is_read']
        ]);
    }

    $statusStmt->execute([$conversationId]);
    $currentStatus = strtolower((string)$statusStmt->fetchColumn());
    if ($currentStatus !== '' && $currentStatus !== $lastStatus) {
        $lastStatus = $currentStatus;
        sse_emit_user('conversation:status_changed', [
            'conversationId' => $conversationId,
            'workflowStatus' => $currentStatus,
            'status' => twc_ui_conversation_status($currentStatus),
        ]);
    }

    $unreadStmt->execute([$conversationId]);
    $unread = (int)$unreadStmt->fetchColumn();
    if ($unread !== $lastUnread) {
        $lastUnread = $unread;
        sse_emit_user('conversation:unread', ['conversationId' => $conversationId, 'unreadCount' => $unread]);
    }

    sse_emit_user('heartbeat', ['ts' => round(microtime(true) * 1000)]);
    sleep(2);
}

sse_emit_user('end', ['reason' => 'poll_window_complete']);

