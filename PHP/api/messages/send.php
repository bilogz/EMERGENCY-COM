<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

$conversation_id = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
$sender_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$content = isset($input['content']) ? trim($input['content']) : '';
$nonce = isset($input['nonce']) ? trim($input['nonce']) : '';
$sender_type = isset($input['sender_type']) ? trim($input['sender_type']) : 'citizen';

if ($conversation_id <= 0 || $sender_id <= 0 || $content === '' || $nonce === '') {
    apiResponse::error('Missing required fields.', 400);
}

try {
    // Check for duplicate nonce
    $stmt = $pdo->prepare('SELECT id FROM messages WHERE nonce = ?');
    $stmt->execute([$nonce]);
    if ($stmt->fetch()) {
        apiResponse::success(null, 'Message already received.');
    }

    // Insert message (Note: sender_type must exist in your messages table)
    $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, content, sender_type, nonce) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$conversation_id, $sender_id, $content, $sender_type, $nonce]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('
        SELECT m.id, m.conversation_id, m.sender_id, u.name AS senderName, m.content AS messageText, m.sent_at, m.nonce
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ');
    $stmt->execute([$id]);
    $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newMessage) {
        $newMessage = [
            'id' => $id,
            'conversation_id' => $conversation_id,
            'sender_id' => $sender_id,
            'senderName' => 'Unknown User',
            'messageText' => $content,
            'sent_at' => date('Y-m-d H:i:s'),
            'nonce' => $nonce
        ];
    }

    apiResponse::success(['data' => $newMessage], 'Message sent successfully', 201);

} catch (PDOException $e) {
    error_log('Message Send DB Error: ' . $e->getMessage());
    apiResponse::error('Failed to send message.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Message Send Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
