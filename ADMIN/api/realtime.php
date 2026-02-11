<?php
/**
 * Realtime SSE endpoint (Admin)
 * Emits:
 * - message:new
 * - conversation:status_changed
 * - conversation:unread
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/chat-logic.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

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

function sse_emit($event, $data) {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    @flush();
}

$conversationId = isset($_GET['conversationId']) ? (int)$_GET['conversationId'] : 0;
$lastMessageId = isset($_GET['lastMessageId']) ? (int)$_GET['lastMessageId'] : 0;

$lastStatus = null;
if ($conversationId > 0) {
    $stmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ? LIMIT 1");
    $stmt->execute([$conversationId]);
    $lastStatus = strtolower((string)$stmt->fetchColumn());
}

$activeStatuses = twc_active_statuses();
$unreadSql = "
    SELECT COUNT(DISTINCT c.conversation_id)
    FROM conversations c
    JOIN chat_messages m ON c.conversation_id = m.conversation_id
    WHERE c.status IN (" . twc_placeholders($activeStatuses) . ")
      AND m.is_read = 0
      AND m.sender_type <> 'admin'
";
$unreadStmt = $pdo->prepare($unreadSql);
$unreadStmt->execute($activeStatuses);
$lastUnread = (int)$unreadStmt->fetchColumn();

sse_emit('ready', [
    'conversationId' => $conversationId > 0 ? $conversationId : null,
    'lastMessageId' => $lastMessageId,
    'unreadCount' => $lastUnread
]);

$maxLoops = 15; // ~30s @ 2s interval
for ($i = 0; $i < $maxLoops; $i++) {
    if (connection_aborted()) {
        break;
    }

    if ($conversationId > 0) {
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
            sse_emit('message:new', [
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

        $statusStmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ? LIMIT 1");
        $statusStmt->execute([$conversationId]);
        $currentStatus = strtolower((string)$statusStmt->fetchColumn());
        if ($currentStatus !== '' && $currentStatus !== $lastStatus) {
            $lastStatus = $currentStatus;
            sse_emit('conversation:status_changed', [
                'conversationId' => $conversationId,
                'workflowStatus' => $currentStatus,
                'status' => twc_ui_conversation_status($currentStatus),
            ]);
        }
    }

    $unreadStmt->execute($activeStatuses);
    $unread = (int)$unreadStmt->fetchColumn();
    if ($unread !== $lastUnread) {
        $lastUnread = $unread;
        sse_emit('conversation:unread', ['unreadCount' => $unread]);
    }

    sse_emit('heartbeat', ['ts' => round(microtime(true) * 1000)]);
    sleep(2);
}

sse_emit('end', ['reason' => 'poll_window_complete']);

