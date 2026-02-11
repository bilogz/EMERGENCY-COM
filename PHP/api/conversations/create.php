<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

// Required fields for creating a conversation
$user_id = isset($input['user_id']) ? trim($input['user_id']) : '';
$user_name = isset($input['user_name']) ? trim($input['user_name']) : 'Guest User';

if (empty($user_id)) {
    apiResponse::error('user_id is required', 400);
}

// Optional fields
$user_email = isset($input['user_email']) ? trim($input['user_email']) : null;
$user_phone = isset($input['user_phone']) ? trim($input['user_phone']) : null;
$user_location = isset($input['user_location']) ? trim($input['user_location']) : null;
$user_concern = isset($input['user_concern']) ? trim($input['user_concern']) : 'general';
$is_guest = isset($input['is_guest']) ? (int)$input['is_guest'] : 1;
$device_info = isset($input['device_info']) ? trim($input['device_info']) : null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    // Check if an active conversation already exists for this user
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE user_id = ? AND status = "active" LIMIT 1');
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        apiResponse::success(['data' => $existing], 'Active conversation already exists.');
    }

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

} catch (PDOException $e) {
    error_log('Conversation Create DB Error: ' . $e->getMessage());
    apiResponse::error('A database error occurred.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Conversation Create Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
