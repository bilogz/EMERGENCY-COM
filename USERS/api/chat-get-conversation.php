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

// Check if device tracking columns exist
function hasDeviceTrackingColumns($pdo) {
    static $hasColumns = null;
    if ($hasColumns === null) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'device_info'");
            $hasColumns = $stmt->fetch() !== false;
        } catch (Exception $e) {
            $hasColumns = false;
        }
    }
    return $hasColumns;
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
    
    // Get device info and IP address (only if columns exist)
    $hasDeviceColumns = hasDeviceTrackingColumns($pdo);
    $ipAddress = $hasDeviceColumns ? getClientIP() : null;
    $deviceInfo = $hasDeviceColumns ? formatDeviceInfoForDB() : null;
    $userAgent = $hasDeviceColumns ? ($_SERVER['HTTP_USER_AGENT'] ?? '') : null;
    
    // If conversationId is provided, just return its status and who closed it
    if ($conversationId && !$userId) {
        // Query without closed_by column (it may not exist in all databases)
        $stmt = $pdo->prepare("
            SELECT conversation_id, status, last_message 
            FROM conversations 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch();
        
        if ($conversation) {
            // Extract admin name from last_message if it contains "Closed by"
            $closedBy = null;
            if (isset($conversation['last_message']) && $conversation['last_message'] && strpos($conversation['last_message'], 'Closed by') === 0) {
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
        $selectFields = "
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
            updated_at";
        
        if ($hasDeviceColumns) {
            $selectFields .= ",
            device_info,
            ip_address,
            user_agent";
        }
        
        $stmt = $pdo->prepare("
            SELECT $selectFields
            FROM conversations 
            WHERE user_id = ? 
              AND status = 'active' 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $conversation = $stmt->fetch();
        
        // If not found by user_id, try device/IP as fallback (only if columns exist)
        if (!$conversation && $hasDeviceColumns) {
            $stmt = $pdo->prepare("
                SELECT $selectFields
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
        $selectFields = "
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
            updated_at";
        
        if ($hasDeviceColumns) {
            $selectFields .= ",
            device_info,
            ip_address,
            user_agent";
        }
        
        $stmt = $pdo->prepare("
            SELECT $selectFields
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
        if ($hasDeviceColumns && !empty($conversation['device_info'])) {
            $deviceInfoParsed = json_decode($conversation['device_info'], true);
        }
        
        // Return existing conversation
        $conversationData = [
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
        ];
        
        if ($hasDeviceColumns) {
            $conversationData['deviceInfo'] = $deviceInfoParsed;
            $conversationData['ipAddress'] = $conversation['ip_address'] ?? null;
            $conversationData['userAgent'] = $conversation['user_agent'] ?? null;
        }
        
        echo json_encode([
            'success' => true,
            'conversationId' => $conversation['conversation_id'],
            'conversation' => $conversationData,
            'isNew' => false
        ]);
    } else {
        // Create new conversation
        if ($hasDeviceColumns) {
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
        } else {
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
        }
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
    error_log('Chat get conversation PDO error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to get/create conversation',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Chat get conversation error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ]);
}

