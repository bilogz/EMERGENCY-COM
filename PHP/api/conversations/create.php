<?php
// Set the content type of the response to JSON
header('Content-Type: application/json; charset=utf-8');

// Include the database connection file
require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

// --- Input Validation ---
// Get the raw JSON POST data
$input = json_decode(file_get_contents('php://input'), true);

// Check if the JSON is valid
if (!$input) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid or missing JSON body']);
    exit();
}

// Get 'user_id' from the input, ensuring it is an integer
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;

// Check if the required ID is provided and is a valid positive number
if ($user_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'user_id is required and must be a positive integer']);
    exit();
}

// --- Database Logic ---
try {
    // 1. CHECK FOR EXISTING CONVERSATION
    // Prepare a statement to prevent SQL injection
    $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    // If a conversation already exists, return it instead of creating a new one
    if ($conversation) {
        echo json_encode(['success' => true, 'message' => 'Conversation already exists.', 'conversation' => $conversation]);
        exit();
    }

    // 2. CREATE NEW CONVERSATION
    // Since no conversation exists, prepare a statement to insert a new one.
    $stmt = $pdo->prepare('INSERT INTO conversations (user_id) VALUES (?)');
    $stmt->execute([$user_id]);
    
    // Get the ID of the newly inserted conversation
    $new_id = (int)$pdo->lastInsertId();

    // Prepare the new conversation data to be returned
    $new_conversation = [
        'id' => $new_id,
        'user_id' => $user_id
    ];

    // Return a success response with the new conversation data
    http_response_code(201); // 201 Created
    echo json_encode(['success' => true, 'message' => 'Conversation created successfully.', 'conversation' => $new_conversation]);

} catch (PDOException $e) {
    // Handle any database errors
    http_response_code(500); // Internal Server Error
    error_log('Conversation creation error: ' . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
?>