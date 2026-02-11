<?php
/**
 * Get Conversations API (Admin)
 * Supports inbox filters, unread-first sorting, and workflow status mapping.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/chat-logic.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$page = 1;
$limit = 50;

try {
    $statusFilter = strtolower(trim((string)($_GET['status'] ?? 'active')));
    $categoryFilter = twc_normalize_category($_GET['category'] ?? ($_GET['department'] ?? ''));
    $priorityFilter = twc_normalize_priority($_GET['priority'] ?? '');
    $search = trim((string)($_GET['q'] ?? $_GET['search'] ?? ''));
    $assignedTo = twc_safe_int($_GET['assigned_to'] ?? null);
    $assignedToMe = filter_var($_GET['assigned_to_me'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $adminSessionId = twc_safe_int($_SESSION['admin_user_id'] ?? null);

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    if (!twc_chat_storage_available($pdo)) {
        echo json_encode([
            'success' => true,
            'conversations' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'total_pages' => 0,
            ],
            'warning' => 'Chat storage tables are unavailable in this local database.',
        ]);
        exit;
    }

    $hasCategoryColumn = twc_column_exists($pdo, 'conversations', 'category');
    $hasPriorityColumn = twc_column_exists($pdo, 'conversations', 'priority');

    $categorySqlExpr = $hasCategoryColumn
        ? "LOWER(COALESCE(NULLIF(c.category, ''), c.user_concern, 'general'))"
        : "LOWER(COALESCE(c.user_concern, 'general'))";

    $prioritySqlExpr = $hasPriorityColumn
        ? "LOWER(COALESCE(NULLIF(c.priority, ''), 'normal'))"
        : "CASE
            WHEN LOWER(COALESCE(c.last_message, '')) REGEXP 'urgent|emergency|critical|fire|flood|earthquake|bomb|accident'
              OR LOWER(COALESCE(c.user_concern, '')) REGEXP 'emergency|fire|flood|earthquake|crime|medical'
            THEN 'urgent'
            ELSE 'normal'
           END";

    $params = [];
    $whereSql = " WHERE 1=1 ";
    $whereSql .= twc_status_filter_clause($statusFilter, $params, 'c');

    if ($categoryFilter !== '' && $categoryFilter !== 'all') {
        $whereSql .= " AND $categorySqlExpr = ? ";
        $params[] = $categoryFilter;
    }

    if (isset($_GET['priority']) && trim((string)$_GET['priority']) !== '' && $_GET['priority'] !== 'all') {
        if ($priorityFilter === 'urgent' || $priorityFilter === 'normal') {
            $whereSql .= " AND $prioritySqlExpr = ? ";
            $params[] = $priorityFilter;
        }
    }

    if ($assignedToMe && $adminSessionId !== null) {
        $whereSql .= " AND c.assigned_to = ? ";
        $params[] = $adminSessionId;
    } elseif ($assignedTo !== null) {
        $whereSql .= " AND c.assigned_to = ? ";
        $params[] = $assignedTo;
    }

    if ($search !== '') {
        $whereSql .= " AND (
            c.user_name LIKE ?
            OR c.user_email LIKE ?
            OR c.user_phone LIKE ?
            OR COALESCE(c.last_message, '') LIKE ?
        ) ";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $countSql = "
        SELECT COUNT(*)
        FROM conversations c
        $whereSql
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();

    $sql = "
        SELECT
            c.conversation_id,
            c.user_id,
            c.user_name,
            c.user_email,
            c.user_phone,
            c.user_location,
            c.user_concern,
            c.is_guest,
            c.device_info,
            c.ip_address,
            c.user_agent,
            c.status AS workflow_status,
            c.assigned_to,
            c.created_at,
            c.updated_at,
            COALESCE(lm.message_text, c.last_message) AS last_message,
            COALESCE(lm.created_at, c.last_message_time, c.updated_at) AS last_message_time,
            COALESCE(uc.unread_user_messages, 0) AS unread_user_messages,
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM chat_messages cm
                    WHERE cm.conversation_id = c.conversation_id
                      AND cm.message_text LIKE '[CALL_ENDED]%'
                ) THEN 1 ELSE 0
            END AS has_call,
            $categorySqlExpr AS category_value,
            $prioritySqlExpr AS priority_value
        FROM conversations c
        LEFT JOIN (
            SELECT cm1.conversation_id, cm1.message_text, cm1.created_at
            FROM chat_messages cm1
            INNER JOIN (
                SELECT conversation_id, MAX(created_at) AS max_created_at
                FROM chat_messages
                GROUP BY conversation_id
            ) cm2
              ON cm1.conversation_id = cm2.conversation_id
             AND cm1.created_at = cm2.max_created_at
        ) lm ON c.conversation_id = lm.conversation_id
        LEFT JOIN (
            SELECT conversation_id, COUNT(*) AS unread_user_messages
            FROM chat_messages
            WHERE is_read = 0 AND sender_type <> 'admin'
            GROUP BY conversation_id
        ) uc ON c.conversation_id = uc.conversation_id
        $whereSql
        ORDER BY
            COALESCE(lm.created_at, c.last_message_time, c.updated_at, c.created_at) DESC,
            c.conversation_id DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($sql);
    $queryParams = $params;
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    $stmt->execute($queryParams);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedConversations = array_map(function ($conv) {
        $deviceInfoParsed = null;
        if (!empty($conv['device_info'])) {
            $decoded = json_decode($conv['device_info'], true);
            $deviceInfoParsed = is_array($decoded) ? $decoded : $conv['device_info'];
        }

        $workflowStatus = strtolower((string)$conv['workflow_status']);
        $uiStatus = twc_ui_conversation_status($workflowStatus);
        $category = twc_normalize_category($conv['category_value'] ?? $conv['user_concern'] ?? '');
        $priority = twc_normalize_priority($conv['priority_value'] ?? '', (string)($conv['last_message'] ?? ''), $category);

        return [
            'id' => (int)$conv['conversation_id'],
            'userId' => $conv['user_id'],
            'userName' => $conv['user_name'],
            'userEmail' => $conv['user_email'],
            'userPhone' => $conv['user_phone'],
            'userLocation' => $conv['user_location'],
            'userConcern' => $conv['user_concern'],
            'category' => $category,
            'department' => $category,
            'priority' => $priority,
            'isGuest' => (bool)$conv['is_guest'],
            'deviceInfo' => $deviceInfoParsed,
            'ipAddress' => $conv['ip_address'] ?? null,
            'userAgent' => $conv['user_agent'] ?? null,
            'status' => $uiStatus,
            'workflowStatus' => $workflowStatus,
            'lastMessage' => $conv['last_message'],
            'lastMessagePreview' => $conv['last_message'],
            'lastMessageAt' => $conv['last_message_time'] ? strtotime((string)$conv['last_message_time']) * 1000 : null,
            'lastMessageTime' => $conv['last_message_time'] ? strtotime((string)$conv['last_message_time']) * 1000 : null,
            'unreadCount' => (int)$conv['unread_user_messages'],
            'assignedTo' => twc_safe_int($conv['assigned_to']),
            'createdAt' => $conv['created_at'] ? strtotime((string)$conv['created_at']) * 1000 : null,
            'updatedAt' => $conv['updated_at'] ? strtotime((string)$conv['updated_at']) * 1000 : null,
            'hasCall' => (bool)($conv['has_call'] ?? false),
        ];
    }, $conversations);

    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'total_pages' => $limit > 0 ? (int)ceil($totalCount / $limit) : 1,
        ],
    ]);
} catch (PDOException $e) {
    $message = $e->getMessage();
    if (stripos($message, "doesn't exist in engine") !== false || stripos($message, 'Base table or view not found') !== false) {
        echo json_encode([
            'success' => true,
            'conversations' => [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'total_pages' => 0,
            ],
            'warning' => 'Chat tables are missing or corrupted in this local database.',
        ]);
        exit;
    }
    error_log('Admin chat get conversations error: ' . $message);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve conversations',
        'error' => $message,
    ]);
} catch (Exception $e) {
    error_log('Admin chat get conversations general error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve conversations',
        'error' => $e->getMessage(),
    ]);
}
