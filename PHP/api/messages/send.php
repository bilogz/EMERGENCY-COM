<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { apiResponse::error('No JSON body', 400); }

$conversation_id = $input['conversation_id'] ?? 0;
$sender_id = $input['sender_id'] ?? $input['user_id'] ?? '';
$message_text = $input['message_text'] ?? $input['content'] ?? '';
$sender_name = $input['sender_name'] ?? 'User';
$sender_type = $input['sender_type'] ?? 'user';

if (!$conversation_id || !$sender_id || !$message_text) {
    apiResponse::error('Missing Data', 400);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$now = date('Y-m-d H:i:s');

try {
    if ($sender_name === 'User') {
        $st = $pdo->prepare('SELECT user_name FROM conversations WHERE conversation_id = ?');
        $st->execute([$conversation_id]);
        $row = $st->fetch();
        if ($row) $sender_name = $row['user_name'];
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, is_read, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
    $stmt->execute([$conversation_id, $sender_id, $sender_name, $sender_type, $message_text, $ip, $ua, $now]);
    
    $new_id = $pdo->lastInsertId();

    $stmtUpd = $pdo->prepare("UPDATE conversations SET last_message = ?, last_message_time = ?, updated_at = NOW() WHERE conversation_id = ?");
    $stmtUpd->execute([$message_text, $now, $conversation_id]);

    $stmtGet = $pdo->prepare("SELECT * FROM chat_messages WHERE message_id = ?");
    $stmtGet->execute([$new_id]);
    $msg = $stmtGet->fetch(PDO::FETCH_ASSOC);

    // Return the message ID clearly so the user knows where to look
    apiResponse::success([
        'message_id' => $new_id,
        'data' => $msg
    ], "Message saved as ID #$new_id");

} catch (Exception $e) {
    apiResponse::error($e->getMessage(), 500);
}
?>
