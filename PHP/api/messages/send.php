<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

// BACKWARDS COMPATIBILITY: Support both old and new field names
$conversation_id = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;

// Old app sends 'user_id', new app sends 'sender_id'
$sender_id = isset($input['sender_id']) ? trim($input['sender_id']) : (isset($input['user_id']) ? trim($input['user_id']) : '');

// Old app doesn't send 'sender_name', we'll try to find it in conversations if missing
$sender_name = isset($input['sender_name']) ? trim($input['sender_name']) : 'User';

// Old app sends 'content', new app sends 'message_text'
$message_text = isset($input['message_text']) ? trim($input['message_text']) : (isset($input['content']) ? trim($input['content']) : '');

$sender_type = isset($input['sender_type']) ? trim($input['sender_type']) : 'user';

if ($conversation_id <= 0 || empty($sender_id) || empty($message_text)) {
    apiResponse::error('Missing required fields. Debug info: ' . json_encode($input), 400);
}

// If name is still 'User', try to fetch the real name from the conversation record
if ($sender_name === 'User') {
    $stmtName = $pdo->prepare('SELECT user_name FROM conversations WHERE conversation_id = ? LIMIT 1');
    $stmtName->execute([$conversation_id]);
    $res = $stmtName->fetch();
    if ($res) $sender_name = $res['user_name'];
}

if (!in_array($sender_type, ['user', 'admin'])) {
    $sender_type = 'user';
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;
$created_at = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    // 1. INSERT into chat_messages
    $stmt = $pdo->prepare('
        INSERT INTO chat_messages (
            conversation_id, sender_id, sender_name, sender_type, 
            message_text, ip_address, device_info, is_read, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
    ');
    
    $stmt->execute([
        $conversation_id, $sender_id, $sender_name, $sender_type, 
        $message_text, $ip_address, $device_info, $created_at
    ]);
    
    $new_message_id = (int)$pdo->lastInsertId();

    // 2. UPDATE conversations
    $stmtUpdate = $pdo->prepare('
        UPDATE conversations 
        SET last_message = ?, last_message_time = ?, updated_at = NOW() 
        WHERE conversation_id = ?
    ');
    $stmtUpdate->execute([$message_text, $created_at, $conversation_id]);

    $pdo->commit();

    // 3. FETCH and return
    $stmtFetch = $pdo->prepare('SELECT * FROM chat_messages WHERE message_id = ?');
    $stmtFetch->execute([$new_message_id]);
    $newMessage = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if ($newMessage) {
        $newMessage['message_id'] = (int)$newMessage['message_id'];
        $newMessage['conversation_id'] = (int)$newMessage['conversation_id'];
        $newMessage['is_read'] = (int)$newMessage['is_read'];
    }

    apiResponse::success(['data' => $newMessage], 'Message sent successfully', 201);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Chat Message Send Error: ' . $e->getMessage());
    apiResponse::error('Server Error: ' . $e->getMessage(), 500);
}
?>
