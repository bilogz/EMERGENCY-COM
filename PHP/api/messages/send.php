<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

// Get the JSON input from the app
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing JSON body']);
    exit();
}

// Get and validate all required parameters from the app
$conversation_id = isset($input['conversation_id']) ? (int)$input['conversation_id'] : 0;
$sender_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$content = isset($input['content']) ? trim($input['content']) : '';
$nonce = isset($input['nonce']) ? trim($input['nonce']) : '';

if ($conversation_id <= 0 || $sender_id <= 0 || $content === '' || $nonce === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: conversation_id, user_id, content, and nonce are required.']);
    exit();
}

try {
    // Check for an existing nonce to prevent creating duplicate messages
    $stmt = $pdo->prepare('SELECT id FROM messages WHERE nonce = ?');
    $stmt->execute([$nonce]);
    if ($stmt->fetch()) {
        http_response_code(200); // OK, but not created
        echo json_encode(['success' => true, 'message' => 'Message already received.']);
        exit();
    }

    // Insert the new message into the corrected table structure
    $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, content, nonce) VALUES (?, ?, ?, ?)');
    $stmt->execute([$conversation_id, $sender_id, $content, $nonce]);
    $id = (int)$pdo->lastInsertId();

    // Fetch and return the full message details, as the app expects
    $stmt = $pdo->prepare('
        SELECT m.id, m.conversation_id, m.sender_id, u.name AS senderName, m.content AS messageText, m.sent_at, m.nonce
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ');
    $stmt->execute([$id]);
    $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newMessage) {
        // Fallback: If the JOIN failed (e.g., user missing), construct the response manually
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

    http_response_code(201); // 201 Created
    echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'data' => $newMessage]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log('Message send error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>