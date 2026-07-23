<?php
// chat.php
// Chat API endpoint for real-time messaging between users and operators

require_once __DIR__ . '/../shared/db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to get client IP
function getClientIp() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Helper function to get device info
function getDeviceInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $info = [
        'user_agent' => $userAgent,
        'platform' => 'unknown'
    ];
    
    if (strpos($userAgent, 'Android') !== false) {
        $info['platform'] = 'android';
    } elseif (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $info['platform'] = 'ios';
    } elseif (strpos($userAgent, 'Windows') !== false) {
        $info['platform'] = 'windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $info['platform'] = 'mac';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $info['platform'] = 'linux';
    }
    
    return json_encode($info);
}

// POST: Create new conversation
if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!is_array($data)) {
        apiResponse::error("Invalid JSON input.", 400);
    }
    
    $user_id = $data['user_id'] ?? null;
    $user_name = $data['user_name'] ?? 'Guest User';
    $user_email = $data['user_email'] ?? null;
    $user_phone = $data['user_phone'] ?? null;
    $user_location = $data['user_location'] ?? null;
    $user_concern = $data['user_concern'] ?? 'general';
    $is_guest = $data['is_guest'] ?? 1;
    $message = $data['message'] ?? '';
    $incident_priority_score = $data['incident_priority_score'] ?? 0;
    $incident_priority_level = $data['incident_priority_level'] ?? 'low';
    $incident_priority_color = $data['incident_priority_color'] ?? 'green';
    $incident_priority_breakdown = $data['incident_priority_breakdown'] ?? null;
    $incident_priority_manual = $data['incident_priority_manual'] ?? 0;
    
    // Validate required fields
    if (!$user_name || !$message) {
        apiResponse::error("Missing required fields: user_name, message", 400);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert into conversations table
        $convSql = "INSERT INTO conversations (
            user_id, user_name, user_email, user_phone, user_location, 
            user_concern, is_guest, status, last_message, last_message_time,
            device_info, ip_address, user_agent, created_at, updated_at,
            incident_priority_score, incident_priority_level, incident_priority_color,
            incident_priority_breakdown, incident_priority_manual
        ) VALUES (
            :user_id, :user_name, :user_email, :user_phone, :user_location,
            :user_concern, :is_guest, 'active', :last_message, NOW(),
            :device_info, :ip_address, :user_agent, NOW(), NOW(),
            :incident_priority_score, :incident_priority_level, :incident_priority_color,
            :incident_priority_breakdown, :incident_priority_manual
        )";
        
        $convStmt = $pdo->prepare($convSql);
        $convStmt->execute([
            ':user_id' => $user_id,
            ':user_name' => $user_name,
            ':user_email' => $user_email,
            ':user_phone' => $user_phone,
            ':user_location' => $user_location,
            ':user_concern' => $user_concern,
            ':is_guest' => $is_guest,
            ':last_message' => substr($message, 0, 255),
            ':device_info' => getDeviceInfo(),
            ':ip_address' => getClientIp(),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':incident_priority_score' => $incident_priority_score,
            ':incident_priority_level' => $incident_priority_level,
            ':incident_priority_color' => $incident_priority_color,
            ':incident_priority_breakdown' => $incident_priority_breakdown,
            ':incident_priority_manual' => $incident_priority_manual
        ]);
        
        $conversationId = $pdo->lastInsertId();
        
        // Insert initial message into chat_messages
        $msgSql = "INSERT INTO chat_messages (
            conversation_id, sender_id, sender_name, sender_type,
            message_text, ip_address, device_info, is_read, created_at
        ) VALUES (
            :conversation_id, :sender_id, :sender_name, 'user',
            :message_text, :ip_address, :device_info, 0, NOW()
        )";
        
        $msgStmt = $pdo->prepare($msgSql);
        $msgStmt->execute([
            ':conversation_id' => $conversationId,
            ':sender_id' => $user_id ?? 'guest',
            ':sender_name' => $user_name,
            ':message_text' => $message,
            ':ip_address' => getClientIp(),
            ':device_info' => getDeviceInfo()
        ]);
        
        $pdo->commit();
        
        apiResponse::success([
            'conversation_id' => $conversationId,
            'status' => 'open',
            'message' => 'Conversation created'
        ], 'Conversation created successfully');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Chat conversation creation failed: ' . $e->getMessage());
        apiResponse::error('Failed to create conversation', 500);
    }
}

// GET: Retrieve conversation messages
elseif ($method === 'GET') {
    $conversationId = $_GET['conversation_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    if (!$conversationId) {
        apiResponse::error("Missing required parameter: conversation_id", 400);
    }
    
    try {
        // Verify user has access to this conversation
        $checkSql = "SELECT user_id FROM conversations WHERE conversation_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$conversationId]);
        $conversation = $checkStmt->fetch();
        
        if (!$conversation) {
            apiResponse::error("Conversation not found", 404);
        }
        
        // Get messages
        $msgSql = "SELECT 
            message_id, conversation_id, sender_id, sender_name, sender_type,
            message_text, attachment_url, attachment_mime, attachment_size,
            is_read, created_at
        FROM chat_messages 
        WHERE conversation_id = ?
        ORDER BY created_at ASC";
        
        $msgStmt = $pdo->prepare($msgSql);
        $msgStmt->execute([$conversationId]);
        $messages = $msgStmt->fetchAll();
        
        // Mark messages as read if user is the recipient
        if ($userId && $conversation['user_id'] == $userId) {
            $updateSql = "UPDATE chat_messages SET is_read = 1 
                         WHERE conversation_id = ? AND sender_type = 'admin'";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$conversationId]);
        }
        
        apiResponse::success(['messages' => $messages], 'Messages retrieved successfully');
        
    } catch (PDOException $e) {
        error_log('Chat messages retrieval failed: ' . $e->getMessage());
        apiResponse::error('Failed to retrieve messages', 500);
    }
}

// PUT: Send message to existing conversation
elseif ($method === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!is_array($data)) {
        apiResponse::error("Invalid JSON input.", 400);
    }
    
    $conversationId = $data['conversation_id'] ?? null;
    $senderId = $data['sender_id'] ?? null;
    $senderName = $data['sender_name'] ?? 'User';
    $senderType = $data['sender_type'] ?? 'user'; // 'user' or 'admin'
    $messageText = $data['message_text'] ?? '';
    $attachmentUrl = $data['attachment_url'] ?? null;
    $attachmentMime = $data['attachment_mime'] ?? null;
    $attachmentSize = $data['attachment_size'] ?? null;
    
    // Validate required fields
    if (!$conversationId || !$messageText) {
        apiResponse::error("Missing required fields: conversation_id, message_text", 400);
    }
    
    // Validate sender_type
    if (!in_array($senderType, ['user', 'admin'])) {
        apiResponse::error("Invalid sender_type. Must be 'user' or 'admin'", 400);
    }
    
    try {
        // Verify conversation exists
        $checkSql = "SELECT conversation_id, status FROM conversations WHERE conversation_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$conversationId]);
        $conversation = $checkStmt->fetch();
        
        if (!$conversation) {
            apiResponse::error("Conversation not found", 404);
        }
        
        if ($conversation['status'] === 'closed' || $conversation['status'] === 'archived') {
            apiResponse::error("Cannot send message to closed conversation", 400);
        }
        
        $pdo->beginTransaction();
        
        // Insert message
        $msgSql = "INSERT INTO chat_messages (
            conversation_id, sender_id, sender_name, sender_type,
            message_text, attachment_url, attachment_mime, attachment_size,
            ip_address, device_info, is_read, created_at
        ) VALUES (
            :conversation_id, :sender_id, :sender_name, :sender_type,
            :message_text, :attachment_url, :attachment_mime, :attachment_size,
            :ip_address, :device_info, 0, NOW()
        )";
        
        $msgStmt = $pdo->prepare($msgSql);
        $msgStmt->execute([
            ':conversation_id' => $conversationId,
            ':sender_id' => $senderId,
            ':sender_name' => $senderName,
            ':sender_type' => $senderType,
            ':message_text' => $messageText,
            ':attachment_url' => $attachmentUrl,
            ':attachment_mime' => $attachmentMime,
            ':attachment_size' => $attachmentSize,
            ':ip_address' => getClientIp(),
            ':device_info' => getDeviceInfo()
        ]);
        
        // Update conversation last message
        $updateConvSql = "UPDATE conversations SET 
            last_message = :last_message,
            last_message_time = NOW(),
            updated_at = NOW()
        WHERE conversation_id = :conversation_id";
        
        $updateConvStmt = $pdo->prepare($updateConvSql);
        $updateConvStmt->execute([
            ':last_message' => substr($messageText, 0, 255),
            ':conversation_id' => $conversationId
        ]);
        
        $pdo->commit();
        
        apiResponse::success([
            'message_id' => $pdo->lastInsertId(),
            'conversation_id' => $conversationId,
            'status' => 'sent'
        ], 'Message sent successfully');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Chat message sending failed: ' . $e->getMessage());
        apiResponse::error('Failed to send message', 500);
    }
}

// DELETE: Close conversation
elseif ($method === 'DELETE') {
    $conversationId = $_GET['conversation_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    if (!$conversationId) {
        apiResponse::error("Missing required parameter: conversation_id", 400);
    }
    
    try {
        // Verify user owns this conversation
        $checkSql = "SELECT user_id, status FROM conversations WHERE conversation_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$conversationId]);
        $conversation = $checkStmt->fetch();
        
        if (!$conversation) {
            apiResponse::error("Conversation not found", 404);
        }
        
        if ($userId && $conversation['user_id'] != $userId) {
            apiResponse::error("You don't have permission to close this conversation", 403);
        }
        
        // Update conversation status
        $updateSql = "UPDATE conversations SET status = 'closed', updated_at = NOW() 
                     WHERE conversation_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$conversationId]);
        
        apiResponse::success(['status' => 'closed'], 'Conversation closed successfully');
        
    } catch (PDOException $e) {
        error_log('Conversation closing failed: ' . $e->getMessage());
        apiResponse::error('Failed to close conversation', 500);
    }
}

else {
    apiResponse::error('Method not allowed', 405);
}
?>
