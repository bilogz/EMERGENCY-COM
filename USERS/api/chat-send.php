<?php
/**
 * Send Chat Message API (User/Citizen)
 * User reply flow: creates/routs thread and keeps status in_progress.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/device_tracking.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

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
    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    $text = trim((string)($input['text'] ?? $_POST['text'] ?? ''));
    $userId = $input['userId'] ?? $_POST['userId'] ?? null;
    $userName = trim((string)($input['userName'] ?? $_POST['userName'] ?? 'Guest User'));
    $userEmail = $input['userEmail'] ?? $_POST['userEmail'] ?? null;
    $userPhone = $input['userPhone'] ?? $_POST['userPhone'] ?? null;
    $userLocation = $input['userLocation'] ?? $_POST['userLocation'] ?? null;
    $userConcern = $input['userConcern'] ?? $_POST['userConcern'] ?? null;
    $rawCategory = $input['category'] ?? $_POST['category'] ?? $userConcern;
    $rawPriority = $input['priority'] ?? $_POST['priority'] ?? null;
    $isGuest = isset($input['isGuest'])
        ? ($input['isGuest'] === '1' || $input['isGuest'] === true)
        : (isset($_POST['isGuest']) ? ($_POST['isGuest'] === '1') : true);
    $conversationId = $input['conversationId'] ?? $_POST['conversationId'] ?? null;
    $conversationId = twc_safe_int($conversationId);

    $ipAddress = function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? null);
    $deviceInfo = function_exists('formatDeviceInfoForDB') ? formatDeviceInfoForDB() : null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if ($text === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message text is required']);
        exit;
    }

    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    $category = twc_normalize_category($rawCategory ?? '');
    $priority = twc_normalize_priority($rawPriority ?? '', $text, $category);

    $hasCategoryColumn = twc_column_exists($pdo, 'conversations', 'category');
    $hasPriorityColumn = twc_column_exists($pdo, 'conversations', 'priority');
    $hasUserIdStringColumn = twc_column_exists($pdo, 'conversations', 'user_id_string');
    $hasAssignedToColumn = twc_column_exists($pdo, 'conversations', 'assigned_to');
    $statusOpen = twc_status_for_db($pdo, 'open');
    $statusInProgress = twc_status_for_db($pdo, 'in_progress');

    if (!empty($conversationId)) {
        $statusStmt = $pdo->prepare("SELECT status FROM conversations WHERE conversation_id = ?");
        $statusStmt->execute([$conversationId]);
        $conversation = $statusStmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            // Stale client-side conversation id; auto-create/reuse active thread below.
            $conversationId = null;
        } elseif (twc_is_closed_status($conversation['status'] ?? '')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'This conversation is closed. Please start a new conversation.',
                'conversationStatus' => 'closed'
            ]);
            exit;
        }
    }

    $pdo->beginTransaction();

    $statusActive = twc_active_statuses();
    $statusInClause = twc_placeholders($statusActive);
    $existingConv = null;

    if (empty($conversationId)) {
        if ($hasUserIdStringColumn && !is_numeric($userId)) {
            $sql = "
                SELECT conversation_id, assigned_to
                FROM conversations
                WHERE user_id_string = ?
                  AND status IN ($statusInClause)
                ORDER BY updated_at DESC
                LIMIT 1
            ";
            $params = [$userId];
            $params = array_merge($params, $statusActive);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // If user_id_string is unavailable and userId is non-numeric,
            // skip user_id matching to avoid guest collisions on implicit 0 casts.
            if (is_numeric($userId)) {
                $sql = "
                    SELECT conversation_id, assigned_to
                    FROM conversations
                    WHERE user_id = ?
                      AND status IN ($statusInClause)
                    ORDER BY updated_at DESC
                    LIMIT 1
                ";
                $params = [$userId];
                $params = array_merge($params, $statusActive);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$existingConv && $isGuest && $ipAddress && $deviceInfo) {
            $sql = "
                SELECT conversation_id, assigned_to
                FROM conversations
                WHERE ip_address = ?
                  AND device_info = ?
                  AND status IN ($statusInClause)
                ORDER BY updated_at DESC
                LIMIT 1
            ";
            $params = [$ipAddress, $deviceInfo];
            $params = array_merge($params, $statusActive);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existingConv = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($existingConv) {
            $conversationId = (int)$existingConv['conversation_id'];
        } else {
            $fallbackAssignee = $hasAssignedToColumn ? twc_pick_assignee($pdo) : null;

            $columns = [
                'user_id',
                'user_name',
                'user_email',
                'user_phone',
                'user_location',
                'user_concern',
                'is_guest',
                'device_info',
                'ip_address',
                'user_agent',
                'status',
                'created_at',
                'updated_at',
            ];
            $values = [
                !is_numeric($userId) ? 0 : $userId,
                $userName,
                $userEmail,
                $userPhone,
                $userLocation,
                $category !== '' ? $category : $userConcern,
                $isGuest ? 1 : 0,
                $deviceInfo,
                $ipAddress,
                $userAgent,
                $statusOpen,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ];

            if ($hasUserIdStringColumn) {
                $columns[] = 'user_id_string';
                $values[] = is_numeric($userId) ? null : (string)$userId;
            }
            if ($hasAssignedToColumn && $fallbackAssignee !== null) {
                $columns[] = 'assigned_to';
                $values[] = $fallbackAssignee;
            }
            if ($hasCategoryColumn) {
                $columns[] = 'category';
                $values[] = $category !== '' ? $category : null;
            }
            if ($hasPriorityColumn) {
                $columns[] = 'priority';
                $values[] = $priority;
            }

            $insertSql = "INSERT INTO conversations (" . implode(',', $columns) . ")
                          VALUES (" . twc_placeholders($values) . ")";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute($values);
            $conversationId = (int)$pdo->lastInsertId();
        }
    }

    $insertMessageStmt = $pdo->prepare("
        INSERT INTO chat_messages
        (conversation_id, sender_id, sender_name, sender_type, message_text, ip_address, device_info, is_read, created_at)
        VALUES (?, ?, ?, 'user', ?, ?, ?, 0, NOW())
    ");
    $insertMessageStmt->execute([
        $conversationId,
        (string)$userId,
        $userName,
        $text,
        $ipAddress,
        $deviceInfo
    ]);
    $messageId = (int)$pdo->lastInsertId();

    $convAssignStmt = $pdo->prepare("SELECT assigned_to FROM conversations WHERE conversation_id = ? LIMIT 1");
    $convAssignStmt->execute([$conversationId]);
    $convNow = $convAssignStmt->fetch(PDO::FETCH_ASSOC);
    $assignedTo = twc_safe_int($convNow['assigned_to'] ?? null);
    if ($assignedTo === null && $hasAssignedToColumn) {
        $assignedTo = twc_pick_assignee($pdo);
    }

    $updateParts = [
        "last_message = ?",
        "last_message_time = NOW()",
        "updated_at = NOW()",
        "status = ?",
        "user_concern = COALESCE(?, user_concern)",
    ];
    $updateParams = [
        $text,
        $statusInProgress,
        $category !== '' ? $category : $userConcern,
    ];

    if ($hasAssignedToColumn && $assignedTo !== null) {
        $updateParts[] = "assigned_to = ?";
        $updateParams[] = $assignedTo;
    }
    if ($hasCategoryColumn && $category !== '') {
        $updateParts[] = "category = ?";
        $updateParams[] = $category;
    }
    if ($hasPriorityColumn) {
        $updateParts[] = "priority = ?";
        $updateParams[] = $priority;
    }

    $updateParams[] = $conversationId;
    $updateSql = "UPDATE conversations SET " . implode(', ', $updateParts) . " WHERE conversation_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);

    if (twc_table_exists($pdo, 'chat_queue')) {
        $queueHasAssigned = twc_column_exists($pdo, 'chat_queue', 'assigned_to');

        $queueColumns = [
            'conversation_id', 'user_id', 'user_name', 'user_email', 'user_phone',
            'user_location', 'user_concern', 'is_guest', 'message', 'status', 'created_at'
        ];
        $queueValues = [
            $conversationId, (string)$userId, $userName, $userEmail, $userPhone,
            $userLocation, ($category !== '' ? $category : $userConcern), $isGuest ? 1 : 0, $text, 'pending', date('Y-m-d H:i:s')
        ];
        if ($queueHasAssigned) {
            $queueColumns[] = 'assigned_to';
            $queueValues[] = $assignedTo;
        }

        $queueSql = "INSERT INTO chat_queue (" . implode(',', $queueColumns) . ")
                     VALUES (" . twc_placeholders($queueValues) . ")
                     ON DUPLICATE KEY UPDATE
                        message = VALUES(message),
                        status = 'pending',
                        updated_at = NOW()";
        if ($queueHasAssigned) {
            $queueSql .= ", assigned_to = VALUES(assigned_to)";
        }
        $queueStmt = $pdo->prepare($queueSql);
        $queueStmt->execute($queueValues);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'messageId' => $messageId,
        'conversationId' => $conversationId,
        'workflowStatus' => $statusInProgress,
        'category' => $category,
        'priority' => $priority,
        'assignedTo' => $assignedTo,
        'message' => 'Message sent successfully'
    ]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chat send error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chat send general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
