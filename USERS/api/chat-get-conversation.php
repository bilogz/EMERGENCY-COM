<?php
/**
 * Get or Create Conversation API
 * Gets existing conversation or creates a new one
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conversationId = $_GET['conversationId'] ?? null;
    $userId = $_GET['userId'] ?? $_POST['userId'] ?? null;
    $userName = $_GET['userName'] ?? $_POST['userName'] ?? 'Guest User';
    $userEmail = $_GET['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $_GET['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $_GET['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $_GET['userConcern'] ?? $_POST['userConcern'] ?? null;
    $isGuest = isset($_GET['isGuest']) ? (bool)$_GET['isGuest'] : (isset($_POST['isGuest']) ? (bool)$_POST['isGuest'] : true);
    
    // Get device info and IP address
    $ipAddress = getClientIP();
    $deviceInfo = formatDeviceInfoForDB();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // If conversationId is provided, just return its status and who closed it
    if ($conversationId && !$userId) {
        $stmt = $pdo->prepare("
            SELECT conversation_id, status, last_message, closed_by 
            FROM conversations 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            // Extract admin name from last_message if it contains "Closed by"
            $closedBy = null;
            if ($conversation['closed_by']) {
                $closedBy = $conversation['closed_by'];
            } elseif ($conversation['last_message'] && strpos($conversation['last_message'], 'Closed by') === 0) {
                $closedBy = str_replace('Closed by ', '', $conversation['last_message']);
            }
            
            echo json_encode([
                'success' => true,
                'conversationId' => $conversation['conversation_id'],
                'status' => $conversation['status'] ?? 'active',
                'closedBy' => $closedBy
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        }
        exit;
    }
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    // For anonymous/guest users, find conversation by user_id first, then device/IP
    // For registered users, find by user_id
    if ($isGuest) {
        // First, try to find by user_id (most reliable for same user)
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
                device_info,
                ip_address,
                user_agent,
                status,
                last_message,
                last_message_time,
                created_at,
                updated_at
            FROM conversations 
            WHERE user_id = ? 
              AND status = 'active' 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $conversation = $stmt->fetch();
        
        // If not found by user_id, try device/IP as fallback
        if (!$conversation) {
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
                    device_info,
                    ip_address,
                    user_agent,
                    status,
                    last_message,
                    last_message_time,
                    created_at,
                    updated_at
                FROM conversations 
                WHERE ip_address = ? 
                  AND device_info = ? 
                  AND status = 'active' 
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$ipAddress, $deviceInfo]);
            $conversation = $stmt->fetch();
        }
    } else {
        // Registered user - find by user_id
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
                device_info,
                ip_address,
                user_agent,
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
    }
    
    if ($conversation) {
        // Parse device info if available
        $deviceInfoParsed = null;
        if (!empty($conversation['device_info'])) {
            $deviceInfoParsed = json_decode($conversation['device_info'], true);
        }
        
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
                'deviceInfo' => $deviceInfoParsed,
                'ipAddress' => $conversation['ip_address'] ?? null,
                'userAgent' => $conversation['user_agent'] ?? null,
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

