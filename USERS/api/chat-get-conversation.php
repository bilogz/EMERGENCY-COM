<?php
/**
 * Get or Create Conversation API (User/Citizen)
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if (!twc_table_exists($pdo, 'conversations')) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Database table not found. Please run SQL migration scripts.',
            'error' => 'Table conversations does not exist'
        ], 500);
    }

    $conversationId = $_GET['conversationId'] ?? null;
    $userId = $_GET['userId'] ?? $_POST['userId'] ?? null;
    $userName = trim((string)($_GET['userName'] ?? $_POST['userName'] ?? 'Guest User'));
    $userEmail = $_GET['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $_GET['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $_GET['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $_GET['userConcern'] ?? $_POST['userConcern'] ?? null;
    $isGuest = isset($_GET['isGuest']) ? (bool)$_GET['isGuest'] : (isset($_POST['isGuest']) ? (bool)$_POST['isGuest'] : true);

    $ipAddress = function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? null);
    $deviceInfo = function_exists('formatDeviceInfoForDB') ? formatDeviceInfoForDB() : null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $hasDeviceColumns = twc_column_exists($pdo, 'conversations', 'device_info');
    $hasUserIdStringColumn = twc_column_exists($pdo, 'conversations', 'user_id_string');
    $hasCategoryColumn = twc_column_exists($pdo, 'conversations', 'category');
    $hasPriorityColumn = twc_column_exists($pdo, 'conversations', 'priority');
    $hasAssignedToColumn = twc_column_exists($pdo, 'conversations', 'assigned_to');
    $statusOpen = twc_status_for_db($pdo, 'open');

    if ($conversationId && !$userId) {
        $stmt = $pdo->prepare("SELECT conversation_id, status, last_message FROM conversations WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            sendJsonResponse(['success' => false, 'message' => 'Conversation not found'], 404);
        }

        $closedBy = null;
        if (!empty($conversation['last_message']) && strpos((string)$conversation['last_message'], 'Closed by ') === 0) {
            $closedBy = str_replace('Closed by ', '', (string)$conversation['last_message']);
        }

        $workflowStatus = strtolower((string)($conversation['status'] ?? 'open'));
        sendJsonResponse([
            'success' => true,
            'conversationId' => (int)$conversation['conversation_id'],
            'status' => twc_ui_conversation_status($workflowStatus),
            'workflowStatus' => $workflowStatus,
            'closedBy' => $closedBy
        ]);
    }

    if (empty($userId)) {
        sendJsonResponse(['success' => false, 'message' => 'User ID is required'], 400);
    }

    $activeStatuses = twc_active_statuses();
    $activeIn = twc_placeholders($activeStatuses);

    $selectColumns = "
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
    ";
    if ($hasDeviceColumns) {
        $selectColumns .= ", device_info, ip_address, user_agent";
    }

    $conversation = null;
    if ($hasUserIdStringColumn && !is_numeric($userId)) {
        $sql = "
            SELECT $selectColumns
            FROM conversations
            WHERE user_id_string = ?
              AND status IN ($activeIn)
            ORDER BY updated_at DESC
            LIMIT 1
        ";
        $params = [$userId];
        $params = array_merge($params, $activeStatuses);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Avoid guest collisions when user_id_string column is unavailable.
        if (is_numeric($userId)) {
            $sql = "
                SELECT $selectColumns
                FROM conversations
                WHERE user_id = ?
                  AND status IN ($activeIn)
                ORDER BY updated_at DESC
                LIMIT 1
            ";
            $params = [$userId];
            $params = array_merge($params, $activeStatuses);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$conversation && $isGuest && $hasDeviceColumns && $ipAddress && $deviceInfo) {
        $sql = "
            SELECT $selectColumns
            FROM conversations
            WHERE ip_address = ?
              AND device_info = ?
              AND status IN ($activeIn)
            ORDER BY updated_at DESC
            LIMIT 1
        ";
        $params = [$ipAddress, $deviceInfo];
        $params = array_merge($params, $activeStatuses);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($conversation) {
        $workflowStatus = strtolower((string)$conversation['status']);
        $conversationData = [
            'id' => (int)$conversation['conversation_id'],
            'userId' => $conversation['user_id'],
            'userName' => $conversation['user_name'],
            'userEmail' => $conversation['user_email'],
            'userPhone' => $conversation['user_phone'],
            'userLocation' => $conversation['user_location'],
            'userConcern' => $conversation['user_concern'],
            'isGuest' => (bool)$conversation['is_guest'],
            'status' => twc_ui_conversation_status($workflowStatus),
            'workflowStatus' => $workflowStatus,
            'lastMessage' => $conversation['last_message'],
            'lastMessageTime' => $conversation['last_message_time'] ? strtotime((string)$conversation['last_message_time']) * 1000 : null,
            'createdAt' => $conversation['created_at'] ? strtotime((string)$conversation['created_at']) * 1000 : null,
            'updatedAt' => $conversation['updated_at'] ? strtotime((string)$conversation['updated_at']) * 1000 : null
        ];
        if ($hasDeviceColumns) {
            $conversationData['deviceInfo'] = !empty($conversation['device_info']) ? json_decode((string)$conversation['device_info'], true) : null;
            $conversationData['ipAddress'] = $conversation['ip_address'] ?? null;
            $conversationData['userAgent'] = $conversation['user_agent'] ?? null;
        }

        sendJsonResponse([
            'success' => true,
            'conversationId' => (int)$conversation['conversation_id'],
            'conversation' => $conversationData,
            'isNew' => false
        ]);
    }

    $category = twc_normalize_category($userConcern ?? '');
    $priority = twc_normalize_priority($_GET['priority'] ?? $_POST['priority'] ?? '', '', $category);
    $assignee = $hasAssignedToColumn ? twc_pick_assignee($pdo) : null;

    $columns = [
        'user_id', 'user_name', 'user_email', 'user_phone', 'user_location',
        'user_concern', 'is_guest', 'status', 'created_at', 'updated_at'
    ];
    $values = [
        !is_numeric($userId) ? 0 : $userId,
        $userName,
        $userEmail,
        $userPhone,
        $userLocation,
        $category !== '' ? $category : $userConcern,
        $isGuest ? 1 : 0,
        $statusOpen,
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s')
    ];

    if ($hasUserIdStringColumn) {
        $columns[] = 'user_id_string';
        $values[] = is_numeric($userId) ? null : (string)$userId;
    }
    if ($hasDeviceColumns) {
        $columns[] = 'device_info';
        $columns[] = 'ip_address';
        $columns[] = 'user_agent';
        $values[] = $deviceInfo;
        $values[] = $ipAddress;
        $values[] = $userAgent;
    }
    if ($hasCategoryColumn) {
        $columns[] = 'category';
        $values[] = $category !== '' ? $category : null;
    }
    if ($hasPriorityColumn) {
        $columns[] = 'priority';
        $values[] = $priority;
    }
    if ($hasAssignedToColumn && $assignee !== null) {
        $columns[] = 'assigned_to';
        $values[] = $assignee;
    }

    $insertSql = "INSERT INTO conversations (" . implode(',', $columns) . ")
                  VALUES (" . twc_placeholders($values) . ")";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute($values);
    $newConversationId = (int)$pdo->lastInsertId();

    sendJsonResponse([
        'success' => true,
        'conversationId' => $newConversationId,
        'conversation' => [
            'id' => $newConversationId,
            'userId' => $userId,
            'userName' => $userName,
            'userEmail' => $userEmail,
            'userPhone' => $userPhone,
            'userLocation' => $userLocation,
            'userConcern' => $category !== '' ? $category : $userConcern,
            'isGuest' => $isGuest,
            'status' => 'active',
            'workflowStatus' => $statusOpen,
            'lastMessage' => null,
            'lastMessageTime' => null,
            'createdAt' => time() * 1000,
            'updatedAt' => time() * 1000
        ],
        'isNew' => true
    ]);
} catch (PDOException $e) {
    error_log('Chat get conversation PDO error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Failed to get/create conversation',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    error_log('Chat get conversation error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ], 500);
}
