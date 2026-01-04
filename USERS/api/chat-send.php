<?php
/**
 * Send Chat Message API
 * Handles sending messages from users to admin
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Get data from POST or JSON
    $text = $input['text'] ?? $_POST['text'] ?? '';
    $userId = $input['userId'] ?? $_POST['userId'] ?? null;
    $userName = $input['userName'] ?? $_POST['userName'] ?? 'Guest User';
    $userEmail = $input['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $input['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $input['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $input['userConcern'] ?? $_POST['userConcern'] ?? null;
    $isGuest = $input['isGuest'] ?? $_POST['isGuest'] ?? true;
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    
    if (empty($text)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message text is required']);
        exit;
    }
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get or create conversation
    if (empty($conversationId)) {
        // Check if user has an active conversation
        $stmt = $pdo->prepare("
            SELECT conversation_id 
            FROM conversations 
            WHERE user_id = ? AND status = 'active' 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $existingConv = $stmt->fetch();
        
        if ($existingConv) {
            $conversationId = $existingConv['conversation_id'];
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
        }
    } else {
        // Update conversation info if provided
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET user_name = COALESCE(?, user_name),
                user_email = COALESCE(?, user_email),
                user_phone = COALESCE(?, user_phone),
                user_location = COALESCE(?, user_location),
                user_concern = COALESCE(?, user_concern),
                updated_at = NOW()
            WHERE conversation_id = ?
        ");
        $stmt->execute([
            $userName,
            $userEmail,
            $userPhone,
            $userLocation,
            $userConcern,
            $conversationId
        ]);
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages 
        (conversation_id, sender_id, sender_name, sender_type, message_text, created_at)
        VALUES (?, ?, ?, 'user', ?, NOW())
    ");
    $stmt->execute([
        $conversationId,
        $userId,
        $userName,
        $text
    ]);
    $messageId = $pdo->lastInsertId();
    
    // Update conversation last message and timestamp
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message = ?, last_message_time = NOW(), updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $stmt->execute([$text, $conversationId]);
    
    // Add to chat queue for admin
    $stmt = $pdo->prepare("
        INSERT INTO chat_queue 
        (conversation_id, user_id, user_name, user_email, user_phone, user_location, user_concern, is_guest, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ON DUPLICATE KEY UPDATE
            message = VALUES(message),
            status = 'pending',
            created_at = NOW()
    ");
    $stmt->execute([
        $conversationId,
        $userId,
        $userName,
        $userEmail,
        $userPhone,
        $userLocation,
        $userConcern,
        $isGuest ? 1 : 0,
        $text
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'messageId' => $messageId,
        'conversationId' => $conversationId,
        'message' => 'Message sent successfully'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

