<?php
/**
 * Save Completed Call to Active List API
 * Adds completed emergency calls to the active conversations list for persistence
 */

session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Use existing database connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

if (!isset($pdo) || $pdo === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$callId = $input['callId'] ?? null;
$userId = $input['userId'] ?? null;
$userName = $input['userName'] ?? 'Unknown User';
$userPhone = $input['userPhone'] ?? null;
$duration = $input['duration'] ?? null;
$endedAt = $input['endedAt'] ?? time();

if (!$callId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Call ID is required']);
    exit();
}

try {
    // Check if this call is already saved
    $stmt = $pdo->prepare("SELECT id FROM completed_calls WHERE call_id = ?");
    $stmt->execute([$callId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['success' => true, 'message' => 'Call already saved']);
        exit;
    }
    
    // Insert completed call into conversations table as a closed conversation
    $stmt = $pdo->prepare("
        INSERT INTO conversations (
            user_id, user_name, user_phone, is_guest, status, 
            last_message, last_message_time, created_at, updated_at
        ) VALUES (?, ?, ?, 0, 'closed', ?, ?, NOW(), NOW())
    ");
    
    $lastMessage = "Emergency call completed (Duration: " . gmdate('H:i:s', $duration) . ")";
    $lastMessageTime = date('Y-m-d H:i:s', $endedAt);
    
    $stmt->execute([
        $userId,
        $userName,
        $userPhone,
        $lastMessage,
        $lastMessageTime
    ]);
    
    $conversationId = $pdo->lastInsertId();
    
    // Also save to completed_calls table for reference
    $stmt = $pdo->prepare("
        INSERT INTO completed_calls (call_id, conversation_id, user_id, user_name, duration, ended_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$callId, $conversationId, $userId, $userName, $duration, $endedAt]);
    
    echo json_encode([
        'success' => true, 
        'conversationId' => $conversationId,
        'message' => 'Call saved to active list'
    ]);
    
} catch (PDOException $e) {
    error_log('Save completed call error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
