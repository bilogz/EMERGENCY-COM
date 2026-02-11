<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

// Support both old and new field names
$user_id = isset($input['user_id']) ? trim($input['user_id']) : '';
$user_name = isset($input['user_name']) ? trim($input['user_name']) : 'Guest User';

if (empty($user_id)) {
    apiResponse::error('user_id is required', 400);
}

$user_email = isset($input['user_email']) ? trim($input['user_email']) : null;
$user_phone = isset($input['user_phone']) ? trim($input['user_phone']) : null;
$user_location = isset($input['user_location']) ? trim($input['user_location']) : null;
$user_concern = isset($input['user_concern']) ? trim($input['user_concern']) : 'general';
$is_guest = isset($input['is_guest']) ? (int)$input['is_guest'] : 1;
$device_info = isset($input['device_info']) ? trim($input['device_info']) : null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    // Check if an active conversation exists
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE user_id = ? AND status = "active" LIMIT 1');
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // FIX: Update the name and details even if the conversation already exists
        // This removes the "User 7" name and replaces it with your real name
        $stmtUpdate = $pdo->prepare('
            UPDATE conversations 
            SET user_name = ?, user_email = ?, user_phone = ?, device_info = ?, updated_at = NOW() 
            WHERE conversation_id = ?
        ');
        $stmtUpdate->execute([$user_name, $user_email, $user_phone, $device_info, $existing['conversation_id']]);
        
        // Refresh data
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE conversation_id = ?');
        $stmt->execute([$existing['conversation_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        apiResponse::success(['data' => $existing], 'Conversation updated with latest user info.');
    }

    // Otherwise, create new
    $stmt = $pdo->prepare('
        INSERT INTO conversations (
            user_id, user_name, user_email, user_phone, user_location, 
            user_concern, is_guest, status, device_info, ip_address, 
            user_agent, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, "active", ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $user_id, $user_name, $user_email, $user_phone, $user_location,
        $user_concern, $is_guest, $device_info, $ip_address, $user_agent
    ]);

    $new_id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE conversation_id = ?');
    $stmt->execute([$new_id]);
    $new_conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    apiResponse::success(['data' => $new_conversation], 'Conversation created successfully.', 201);

} catch (Exception $e) {
    apiResponse::error('Server Error: ' . $e->getMessage(), 500);
}
?>
