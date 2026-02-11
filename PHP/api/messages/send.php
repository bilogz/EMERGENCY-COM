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

// Validate sender_type
if (!in_array($sender_type, ['user', 'admin'])) {
    $sender_type = 'user';
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$device_info = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    // Insert into chat_messages
    $stmt = $pdo->prepare('
        INSERT INTO chat_messages (
            conversation_id, sender_id, sender_name, sender_type, 
            message_text, ip_address, device_info, is_read, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ');
    
    $stmt->execute([
        $conversation_id, $sender_id, $sender_name, $sender_type, 
        $message_text, $ip_address, $device_info
    ]);
    
    $new_message_id = (int)$pdo->lastInsertId();

    // Update last_message and last_message_time in conversations table
    $stmt = $pdo->prepare('
        UPDATE conversations 
        SET last_message = ?, last_message_time = NOW(), updated_at = NOW() 
        WHERE conversation_id = ?
    ');
    $stmt->execute([$message_text, $conversation_id]);

    // Fetch the inserted message to return it
    $stmt = $pdo->prepare('SELECT * FROM chat_messages WHERE message_id = ?');
    $stmt->execute([$new_message_id]);
    $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);

    // Use 'data' instead of 'message' to avoid collision with apiResponse message
    apiResponse::success(['data' => $newMessage], 'Message sent successfully', 201);

} catch (PDOException $e) {
    error_log('Chat Message Send DB Error: ' . $e->getMessage());
    apiResponse::error('Failed to send message.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Chat Message Send Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
