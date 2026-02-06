<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

if (!isset($_GET['conversation_id'])) {
    apiResponse::error('Missing conversation_id.', 400);
}

$conversation_id = (int)$_GET['conversation_id'];
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if ($conversation_id <= 0) {
    apiResponse::error('Invalid conversation_id.', 400);
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

    apiResponse::success(['messages' => $messages], 'OK');

} catch (PDOException $e) {
    error_log('Messages list DB error: ' . $e->getMessage());
    apiResponse::error('A database error occurred.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Messages list error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
