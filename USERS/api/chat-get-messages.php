<?php
/**
 * Get Chat Messages API (User/Citizen)
 * Retrieves thread messages and marks inbound admin messages as read.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conversationId = isset($_GET['conversationId']) ? (int)$_GET['conversationId'] : 0;
    $userId = $_GET['userId'] ?? null;
    $lastMessageId = isset($_GET['lastMessageId']) ? (int)$_GET['lastMessageId'] : 0;

    if ($conversationId <= 0 && empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'conversationId or userId is required']);
        exit;
    }

    if ($conversationId <= 0 && !empty($userId)) {
        $active = twc_active_statuses();
        $stmt = $pdo->prepare("
            SELECT conversation_id
            FROM conversations
            WHERE user_id = ? AND status IN (" . twc_placeholders($active) . ")
            ORDER BY updated_at DESC, conversation_id DESC
            LIMIT 1
        ");
        $params = [$userId];
        $params = array_merge($params, $active);
        $stmt->execute($params);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conv) {
            $conversationId = (int)$conv['conversation_id'];
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'conversationId' => null]);
            exit;
        }
    }

    $statusStmt = $pdo->prepare("
        SELECT status, last_message
        FROM conversations
        WHERE conversation_id = ?
        LIMIT 1
    ");
    $statusStmt->execute([$conversationId]);
    $conversation = $statusStmt->fetch(PDO::FETCH_ASSOC);

    $workflowStatus = strtolower((string)($conversation['status'] ?? 'open'));
    $conversationStatus = twc_ui_conversation_status($workflowStatus);

    $closedBy = null;
    if (!empty($conversation['last_message']) && strpos((string)$conversation['last_message'], 'Closed by ') === 0) {
        $closedBy = str_replace('Closed by ', '', (string)$conversation['last_message']);
    }

    $hasReadAt = twc_column_exists($pdo, 'chat_messages', 'read_at');
    $messageSelectReadAt = $hasReadAt ? ', read_at' : ', NULL AS read_at';
    $hasAttachmentUrl = twc_column_exists($pdo, 'chat_messages', 'attachment_url');
    $hasAttachmentMime = twc_column_exists($pdo, 'chat_messages', 'attachment_mime');
    $hasAttachmentSize = twc_column_exists($pdo, 'chat_messages', 'attachment_size');
    $messageSelectAttachmentUrl = $hasAttachmentUrl ? ', attachment_url' : ', NULL AS attachment_url';
    $messageSelectAttachmentMime = $hasAttachmentMime ? ', attachment_mime' : ', NULL AS attachment_mime';
    $messageSelectAttachmentSize = $hasAttachmentSize ? ', attachment_size' : ', NULL AS attachment_size';

    if ($lastMessageId === 0) {
        $stmt = $pdo->prepare("
            SELECT
                message_id,
                conversation_id,
                sender_id,
                sender_name,
                sender_type,
                message_text,
                created_at,
                is_read
                $messageSelectReadAt
                $messageSelectAttachmentUrl
                $messageSelectAttachmentMime
                $messageSelectAttachmentSize
            FROM chat_messages
            WHERE conversation_id = ?
            ORDER BY message_id ASC
        ");
        $stmt->execute([$conversationId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                message_id,
                conversation_id,
                sender_id,
                sender_name,
                sender_type,
                message_text,
                created_at,
                is_read
                $messageSelectReadAt
                $messageSelectAttachmentUrl
                $messageSelectAttachmentMime
                $messageSelectAttachmentSize
            FROM chat_messages
            WHERE conversation_id = ? AND message_id > ?
            ORDER BY message_id ASC
        ");
        $stmt->execute([$conversationId, $lastMessageId]);
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($messages)) {
        $idsToMarkRead = [];
        foreach ($messages as $msg) {
            if (strtolower((string)$msg['sender_type']) === 'admin' && (int)$msg['is_read'] === 0) {
                $idsToMarkRead[] = (int)$msg['message_id'];
            }
        }
        if (!empty($idsToMarkRead)) {
            $updateSql = "UPDATE chat_messages SET is_read = 1";
            if ($hasReadAt) {
                $updateSql .= ", read_at = NOW()";
            }
            $updateSql .= " WHERE message_id IN (" . twc_placeholders($idsToMarkRead) . ")";
            $markStmt = $pdo->prepare($updateSql);
            $markStmt->execute($idsToMarkRead);
        }
    }

    $formattedMessages = array_map(function ($msg) {
        $isRead = (bool)$msg['is_read'];
        $attachmentUrl = twc_normalize_public_url($msg['attachment_url'] ?? null);
        return [
            'id' => (int)$msg['message_id'],
            'conversationId' => (int)$msg['conversation_id'],
            'senderId' => $msg['sender_id'],
            'senderName' => $msg['sender_name'] ?? null,
            'senderType' => $msg['sender_type'],
            'senderRole' => $msg['sender_type'] === 'admin' ? 'staff' : 'citizen',
            'text' => $msg['message_text'],
            'timestamp' => strtotime((string)$msg['created_at']) * 1000,
            'read' => $isRead,
            'readAt' => !empty($msg['read_at']) ? strtotime((string)$msg['read_at']) * 1000 : null,
            'deliveryStatus' => $isRead ? 'delivered' : 'sent',
            'imageUrl' => $attachmentUrl,
            'attachmentUrl' => $attachmentUrl,
            'attachmentMime' => $msg['attachment_mime'] ?? null,
            'attachmentSize' => isset($msg['attachment_size']) ? (int)$msg['attachment_size'] : null,
        ];
    }, $messages);

    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'conversationId' => $conversationId,
        'conversationStatus' => $conversationStatus,
        'workflowStatus' => $workflowStatus,
        'closedBy' => $closedBy
    ]);
} catch (PDOException $e) {
    error_log('Chat get messages error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve messages',
        'error' => $e->getMessage()
    ]);
}

