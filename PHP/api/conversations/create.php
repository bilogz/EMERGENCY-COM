<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    apiResponse::error('Invalid or missing JSON body', 400);
}

$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($user_id <= 0) {
    apiResponse::error('user_id is required and must be a positive integer', 400);
}

try {
    $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        apiResponse::success(['conversation' => $conversation], 'Conversation already exists.');
    }

    $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
    $stmt->execute([$user_id]);
    
    $new_id = (int)$pdo->lastInsertId();

    $new_conversation = [
        'id' => $new_id,
        'user_id' => $user_id
    ];

    apiResponse::success(['conversation' => $new_conversation], 'Conversation created successfully.', 201);

} catch (PDOException $e) {
    error_log('Conversation DB Error: ' . $e->getMessage());
    apiResponse::error('A database error occurred.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Conversation Error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>
