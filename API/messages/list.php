<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

if (!isset($_GET['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Missing conversation_id.']);
    exit();
}

$conversation_id = (int)$_GET['conversation_id'];
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if ($conversation_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Invalid conversation_id.']);
    exit();
}

try {
    $stmt = $pdo->prepare('
        SELECT
            m.id,
            m.conversation_id,
            m.sender_id,
            u.name as senderName,
            m.content as messageText,
            m.sent_at,
            m.nonce
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? AND m.id > ?
        ORDER BY m.id ASC
    ');

    $stmt->execute([$conversation_id, $last_message_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Messages list error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'messages' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>