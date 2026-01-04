<?php
/**
 * Get or Create Conversation API
 * Gets existing conversation or creates a new one
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $userId = $_GET['userId'] ?? $_POST['userId'] ?? null;
    $userName = $_GET['userName'] ?? $_POST['userName'] ?? 'Guest User';
    $userEmail = $_GET['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $_GET['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $_GET['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $_GET['userConcern'] ?? $_POST['userConcern'] ?? null;
    $isGuest = isset($_GET['isGuest']) ? (bool)$_GET['isGuest'] : (isset($_POST['isGuest']) ? (bool)$_POST['isGuest'] : true);
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    // Check if user has an active conversation
    $stmt = $pdo->prepare("
        SELECT 
            conversation_id,
            user_id,
            user_name,
            user_email,
            user_phone,
            user_location,
            user_concern,
            is_guest,
            status,
            last_message,
            last_message_time,
            created_at,
            updated_at
        FROM conversations 
        WHERE user_id = ? AND status = 'active' 
        ORDER BY updated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // Return existing conversation
        echo json_encode([
            'success' => true,
            'conversationId' => $conversation['conversation_id'],
            'conversation' => [
                'id' => $conversation['conversation_id'],
                'userId' => $conversation['user_id'],
                'userName' => $conversation['user_name'],
                'userEmail' => $conversation['user_email'],
                'userPhone' => $conversation['user_phone'],
                'userLocation' => $conversation['user_location'],
                'userConcern' => $conversation['user_concern'],
                'isGuest' => (bool)$conversation['is_guest'],
                'status' => $conversation['status'],
                'lastMessage' => $conversation['last_message'],
                'lastMessageTime' => $conversation['last_message_time'] ? strtotime($conversation['last_message_time']) * 1000 : null,
                'createdAt' => strtotime($conversation['created_at']) * 1000,
                'updatedAt' => strtotime($conversation['updated_at']) * 1000
            ],
            'isNew' => false
        ]);
    } else {
        // Create new conversation
        $stmt = $pdo->prepare("
            INSERT INTO conversations 
            (user_id, user_name, user_email, user_phone, user_location, user_concern, is_guest, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $userId,
            $userName,
            $userEmail,
            $userPhone,
            $userLocation,
            $userConcern,
            $isGuest ? 1 : 0
        ]);
        $conversationId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'conversationId' => $conversationId,
            'conversation' => [
                'id' => $conversationId,
                'userId' => $userId,
                'userName' => $userName,
                'userEmail' => $userEmail,
                'userPhone' => $userPhone,
                'userLocation' => $userLocation,
                'userConcern' => $userConcern,
                'isGuest' => $isGuest,
                'status' => 'active',
                'lastMessage' => null,
                'lastMessageTime' => null,
                'createdAt' => time() * 1000,
                'updatedAt' => time() * 1000
            ],
            'isNew' => true
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Chat get conversation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to get/create conversation']);
}

