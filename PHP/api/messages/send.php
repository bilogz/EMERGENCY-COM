<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

$conversation_id = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
$sender_id = isset($input['sender_id']) ? trim($input['sender_id']) : '';
$sender_name = isset($input['sender_name']) ? trim($input['sender_name']) : 'Unknown';
$sender_type = isset($input['sender_type']) ? trim($input['sender_type']) : 'user';
$message_text = isset($input['message_text']) ? trim($input['message_text']) : '';

if ($conversation_id <= 0 || empty($sender_id) || empty($message_text)) {
    apiResponse::error('Missing required fields.', 400);
}

if (!in_array($sender_type, ['user', 'admin'])) {
    $sender_type = 'user';
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;
$created_at = date('Y-m-d H:i:s');

try {
    // 1. INSERT INTO chat_messages
    $stmt = $pdo->prepare('
        INSERT INTO chat_messages (
            conversation_id, sender_id, sender_name, sender_type, 
            message_text, ip_address, device_info, is_read, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
    ');
    
    $success = $stmt->execute([
        $conversation_id, $sender_id, $sender_name, $sender_type, 
        $message_text, $ip_address, $device_info, $created_at
    ]);
    
    $new_message_id = (int)$pdo->lastInsertId();

    if (!$success || $new_message_id === 0) {
        throw new Exception("Insert failed or lastInsertId is 0");
    }

    // 2. UPDATE conversations table
    $stmtUpdate = $pdo->prepare('
        UPDATE conversations 
        SET last_message = ?, last_message_time = ?, updated_at = NOW() 
        WHERE conversation_id = ?
    ');
    $stmtUpdate->execute([$message_text, $created_at, $conversation_id]);

    // 3. FETCH the inserted message
    $stmtFetch = $pdo->prepare('SELECT * FROM chat_messages WHERE message_id = ?');
    $stmtFetch->execute([$new_message_id]);
    $newMessage = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    // Ensure types are correct for Kotlin
    if ($newMessage) {
        $newMessage['message_id'] = (int)$newMessage['message_id'];
        $newMessage['conversation_id'] = (int)$newMessage['conversation_id'];
        $newMessage['is_read'] = (int)$newMessage['is_read'];
    }

    apiResponse::success(['data' => $newMessage], 'Message sent successfully', 201);

} catch (PDOException $e) {
    error_log('Chat Message Send DB Error: ' . $e->getMessage());
    apiResponse::error('Database Error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Chat Message Send Error: ' . $e->getMessage());
    apiResponse::error('Server Error: ' . $e->getMessage(), 500);
}
?>
