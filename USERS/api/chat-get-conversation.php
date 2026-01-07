<?php
/**
 * Get or Create Conversation API
 * Gets existing conversation or creates a new one
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Check if conversations table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'conversations'");
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    if (!$tableExists) {
        error_log('ERROR: conversations table does not exist');
        sendJsonResponse([
            'success' => false, 
            'message' => 'Database table not found. Please run the SQL migration scripts.',
            'error' => 'Table conversations does not exist'
        ], 500);
    }
} catch (PDOException $e) {
    error_log('Error checking table existence: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false, 
        'message' => 'Database error',
        'error' => $e->getMessage()
    ], 500);
}

// Check if device tracking columns exist
function hasDeviceTrackingColumns($pdo) {
    static $hasColumns = null;
    if ($hasColumns === null) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM conversations LIKE 'device_info'");
            $hasColumns = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Exception $e) {
            error_log('Error checking device columns: ' . $e->getMessage());
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
    
    // Get device info and IP address (only if columns exist and functions are available)
    $hasDeviceColumns = false;
    $ipAddress = null;
    $deviceInfo = null;
    $userAgent = null;
    
    try {
        $hasDeviceColumns = hasDeviceTrackingColumns($pdo);
        if ($hasDeviceColumns && function_exists('getClientIP') && function_exists('formatDeviceInfoForDB')) {
            try {
                $ipAddress = getClientIP();
                $deviceInfo = formatDeviceInfoForDB();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            } catch (Exception $deviceError) {
                error_log('Error getting device info: ' . $deviceError->getMessage());
                $hasDeviceColumns = false;
                $ipAddress = null;
                $deviceInfo = null;
                $userAgent = null;
            }
        }
    } catch (Exception $e) {
        error_log('Error checking device columns: ' . $e->getMessage());
        $hasDeviceColumns = false;
        $ipAddress = null;
        $deviceInfo = null;
        $userAgent = null;
    }
    
    // If conversationId is provided, just return its status and who closed it
    if ($conversationId && !$userId) {
        $stmt = $pdo->prepare("
            SELECT conversation_id, status, last_message 
            FROM conversations 
            WHERE conversation_id = ?
        ");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conversation) {
            $closedBy = null;
            if (isset($conversation['last_message']) && $conversation['last_message'] && strpos($conversation['last_message'], 'Closed by') === 0) {
                $closedBy = str_replace('Closed by ', '', $conversation['last_message']);
            }
            
            sendJsonResponse([
                'success' => true,
                'conversationId' => $conversation['conversation_id'],
                'status' => $conversation['status'] ?? 'active',
                'closedBy' => $closedBy
            ]);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'Conversation not found'], 404);
        }
    }
    
    if (empty($userId)) {
        sendJsonResponse(['success' => false, 'message' => 'User ID is required'], 400);
    }
    
    // Find existing conversation
    $conversation = null;
    $sql = null;
    
    try {
        if ($isGuest) {
            $sql = "
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
                    updated_at";
            
            if ($hasDeviceColumns) {
                $sql .= ",
                    device_info,
                    ip_address,
                    user_agent";
            }
            
            $sql .= "
                FROM conversations 
                WHERE user_id = ? 
                  AND status = 'active' 
                ORDER BY updated_at DESC 
                LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found by user_id, try device/IP as fallback
            if (!$conversation && $hasDeviceColumns && $ipAddress && $deviceInfo) {
                $sql = "
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
                    LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ipAddress, $deviceInfo]);
                $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $sql = "
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
                    updated_at";
            
            if ($hasDeviceColumns) {
                $sql .= ",
                    device_info,
                    ip_address,
                    user_agent";
            }
            
            $sql .= "
                FROM conversations 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY updated_at DESC 
                LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Error fetching conversation: ' . $e->getMessage());
        error_log('SQL: ' . ($sql ?? 'N/A'));
        // Try with basic fields only as fallback
        try {
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
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            $hasDeviceColumns = false;
        } catch (PDOException $e2) {
            error_log('Fallback query also failed: ' . $e2->getMessage());
            throw $e;
        }
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
        
        sendJsonResponse([
            'success' => true,
            'conversationId' => $conversation['conversation_id'],
            'conversation' => $conversationData,
            'isNew' => false
        ]);
    } else {
        // Create new conversation
        $conversationId = null;
        try {
            if ($hasDeviceColumns && $deviceInfo !== null && $ipAddress !== null) {
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
        } catch (PDOException $e) {
            error_log('Error creating conversation: ' . $e->getMessage());
            // Try without device columns as fallback
            if ($hasDeviceColumns) {
                try {
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
                } catch (PDOException $e2) {
                    error_log('Fallback INSERT also failed: ' . $e2->getMessage());
                    throw $e2;
                }
            } else {
                throw $e;
            }
        }
        
        sendJsonResponse([
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
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'SQLSTATE') !== false) {
        error_log('Full SQL error: ' . $errorMsg);
        $errorMsg = 'Database query failed';
    }
    sendJsonResponse([
        'success' => false, 
        'message' => 'Failed to get/create conversation',
        'error' => $errorMsg
    ], 500);
} catch (Exception $e) {
    error_log('Chat get conversation error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false, 
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ], 500);
}
