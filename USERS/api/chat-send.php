<?php
/**
 * Send Chat Message API
 * Handles sending messages from users to admin
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';

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
    // Get data from POST (FormData) or JSON
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $text = $input['text'] ?? $_POST['text'] ?? '';
    $userId = $input['userId'] ?? $_POST['userId'] ?? null;
    $userName = $input['userName'] ?? $_POST['userName'] ?? 'Guest User';
    $userEmail = $input['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $input['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $input['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $input['userConcern'] ?? $_POST['userConcern'] ?? null;
    $isGuest = isset($input['isGuest']) ? ($input['isGuest'] === '1' || $input['isGuest'] === true) : (isset($_POST['isGuest']) ? ($_POST['isGuest'] === '1') : true);
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    
    // Get device info and IP address
    $ipAddress = getClientIP();
    $deviceInfo = formatDeviceInfoForDB();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
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
    
    // Check if conversation is closed
    if (!empty($conversationId)) {
        $stmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch();
        
        if ($conversation && $conversation['status'] === 'closed') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This conversation is closed. You cannot send messages.']);
            exit;
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get or create conversation
    if (empty($conversationId)) {
        // For anonymous/guest users, find conversation by user_id first, then device/IP
        // For registered users, find by user_id
        if ($isGuest) {
            // First, try to find by user_id (most reliable for same user)
            $stmt = $pdo->prepare("
                SELECT conversation_id 
                FROM conversations 
                WHERE user_id = ? 
                  AND status = 'active' 
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $existingConv = $stmt->fetch();
            
            // If not found by user_id, try device/IP as fallback
            if (!$existingConv) {
                $stmt = $pdo->prepare("
                    SELECT conversation_id 
                    FROM conversations 
                    WHERE ip_address = ? 
                      AND device_info = ? 
                      AND status = 'active' 
                    ORDER BY updated_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$ipAddress, $deviceInfo]);
                $existingConv = $stmt->fetch();
            }
        } else {
            // Registered user - find by user_id
            $stmt = $pdo->prepare("
                SELECT conversation_id 
                FROM conversations 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $existingConv = $stmt->fetch();
        }
        
        if ($existingConv) {
            $conversationId = $existingConv['conversation_id'];
        } else {
            // Create new conversation
            $stmt = $pdo->prepare("
                INSERT INTO conversations 
                (user_id, user_name, user_email, user_phone, user_location, user_concern, is_guest, device_info, ip_address, user_agent, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([
                $userId,
                $userName,
                $userEmail,
                $userPhone,
                $userLocation,
                $userConcern,
                $isGuest ? 1 : 0,
                $deviceInfo,
                $ipAddress,
                $userAgent
            ]);
            $conversationId = $pdo->lastInsertId();
        }
    } else {
        // Update conversation info if provided (also update device/IP if changed)
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET user_name = COALESCE(?, user_name),
                user_email = COALESCE(?, user_email),
                user_phone = COALESCE(?, user_phone),
                user_location = COALESCE(?, user_location),
                user_concern = COALESCE(?, user_concern),
                device_info = COALESCE(?, device_info),
                ip_address = COALESCE(?, ip_address),
                user_agent = COALESCE(?, user_agent),
                updated_at = NOW()
            WHERE conversation_id = ?
        ");
        $stmt->execute([
            $userName,
            $userEmail,
            $userPhone,
            $userLocation,
            $userConcern,
            $deviceInfo,
            $ipAddress,
            $userAgent,
            $conversationId
        ]);
    }
    
    // Insert message (with device/IP tracking)
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages 
        (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, created_at)
        VALUES (?, ?, ?, 'user', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $conversationId,
        $userId,
        $userName,
        $text,
        $ipAddress,
        $deviceInfo
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

