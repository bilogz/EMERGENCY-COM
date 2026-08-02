<?php
/**
 * Reports and General Enquiries trash API.
 *
 * GET  lists soft-deleted conversations.
 * POST moves a conversation to trash, restores it, or deletes it permanently.
 */

header('Content-Type: application/json; charset=utf-8');

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

if (!twc_ensure_conversation_trash_storage($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Trash storage is unavailable']);
    exit;
}

function twc_trash_reasons(): array {
    return [
        'duplicate' => 'Duplicate',
        'false_report' => 'False report',
        'spam' => 'Spam or irrelevant',
        'test_report' => 'Test submission',
        'resolved_elsewhere' => 'Resolved elsewhere',
        'privacy_request' => 'Privacy request',
        'other' => 'Other',
    ];
}

function twc_trash_admin_id(): ?int {
    return twc_safe_int($_SESSION['admin_user_id'] ?? null);
}

function twc_trash_admin_name(): string {
    return trim((string)($_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator')) ?: 'Administrator';
}

function twc_trash_snapshot(array $conversation, int $messageCount): array {
    return [
        'conversation_id' => (int)($conversation['conversation_id'] ?? 0),
        'user_id' => $conversation['user_id'] ?? null,
        'user_name' => $conversation['user_name'] ?? null,
        'user_email' => $conversation['user_email'] ?? null,
        'user_phone' => $conversation['user_phone'] ?? null,
        'user_location' => $conversation['user_location'] ?? null,
        'user_concern' => $conversation['user_concern'] ?? null,
        'workflow_status' => $conversation['status'] ?? 'open',
        'assigned_to' => $conversation['assigned_to'] ?? null,
        'last_message' => $conversation['last_message'] ?? null,
        'last_message_time' => $conversation['last_message_time'] ?? null,
        'created_at' => $conversation['created_at'] ?? null,
        'updated_at' => $conversation['updated_at'] ?? null,
        'message_count' => $messageCount,
    ];
}

function twc_log_deletion_action(
    PDO $pdo,
    int $conversationId,
    ?int $trashId,
    string $action,
    string $itemType,
    ?string $reason,
    ?string $details,
    ?int $adminId,
    string $adminName,
    array $snapshot
): void {
    $stmt = $pdo->prepare("
        INSERT INTO twc_conversation_deletion_audit
            (conversation_id, trash_id, action, item_type, deletion_reason,
             deletion_details, admin_id, admin_name, item_name, item_location,
             last_message, ip_address, user_agent, snapshot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $conversationId,
        $trashId,
        $action,
        $itemType,
        $reason,
        $details,
        $adminId,
        $adminName,
        $snapshot['user_name'] ?? null,
        $snapshot['user_location'] ?? null,
        $snapshot['last_message'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

function twc_delete_conversation_content(PDO $pdo, int $conversationId): void {
    if (twc_table_exists($pdo, 'chat_messages')) {
        $stmt = $pdo->prepare('DELETE FROM chat_messages WHERE conversation_id = ?');
        $stmt->execute([$conversationId]);
    }

    if (twc_table_exists($pdo, 'completed_calls')
        && twc_column_exists($pdo, 'completed_calls', 'conversation_id')) {
        $stmt = $pdo->prepare('DELETE FROM completed_calls WHERE conversation_id = ?');
        $stmt->execute([$conversationId]);
    }

    $stmt = $pdo->prepare('DELETE FROM conversations WHERE conversation_id = ?');
    $stmt->execute([$conversationId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(10, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $type = strtolower(trim((string)($_GET['type'] ?? 'all')));
        $reason = strtolower(trim((string)($_GET['reason'] ?? 'all')));
        $search = trim((string)($_GET['search'] ?? ''));

        $where = ['1=1'];
        $params = [];

        if (in_array($type, ['report', 'general_enquiry'], true)) {
            $where[] = 't.item_type = ?';
            $params[] = $type;
        }
        if (array_key_exists($reason, twc_trash_reasons())) {
            $where[] = 't.deletion_reason = ?';
            $params[] = $reason;
        }
        if ($search !== '') {
            $where[] = "(COALESCE(c.user_name, '') LIKE ?
                OR COALESCE(c.user_location, '') LIKE ?
                OR COALESCE(c.last_message, '') LIKE ?
                OR CAST(t.conversation_id AS CHAR) LIKE ?)";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM twc_conversation_trash t
            LEFT JOIN conversations c ON c.conversation_id = t.conversation_id
            WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.conversation_id,
                t.item_type,
                t.deletion_reason,
                t.deletion_details,
                t.deleted_by_admin_id,
                t.deleted_by_admin_name,
                t.conversation_snapshot,
                t.deleted_at,
                c.user_name,
                c.user_phone,
                c.user_location,
                c.user_concern,
                c.last_message,
                c.status,
                (SELECT COUNT(*) FROM chat_messages cm
                 WHERE cm.conversation_id = t.conversation_id) AS message_count
            FROM twc_conversation_trash t
            LEFT JOIN conversations c ON c.conversation_id = t.conversation_id
            WHERE {$whereSql}
            ORDER BY t.deleted_at DESC, t.id DESC
            LIMIT ? OFFSET ?
        ");
        $queryParams = $params;
        $queryParams[] = $limit;
        $queryParams[] = $offset;
        $stmt->execute($queryParams);

        $reasonLabels = twc_trash_reasons();
        $items = array_map(static function (array $row) use ($reasonLabels): array {
            $snapshot = json_decode((string)($row['conversation_snapshot'] ?? ''), true);
            if (!is_array($snapshot)) {
                $snapshot = [];
            }

            return [
                'id' => (int)$row['id'],
                'conversationId' => (int)$row['conversation_id'],
                'itemType' => $row['item_type'],
                'itemTypeLabel' => $row['item_type'] === 'general_enquiry' ? 'General enquiry' : 'Report',
                'reason' => $row['deletion_reason'],
                'reasonLabel' => $reasonLabels[$row['deletion_reason']] ?? ucfirst(str_replace('_', ' ', $row['deletion_reason'])),
                'details' => $row['deletion_details'],
                'deletedByAdminId' => twc_safe_int($row['deleted_by_admin_id']),
                'deletedBy' => $row['deleted_by_admin_name'],
                'deletedAt' => $row['deleted_at'],
                'userName' => $row['user_name'] ?? $snapshot['user_name'] ?? 'Unknown',
                'userPhone' => $row['user_phone'] ?? $snapshot['user_phone'] ?? null,
                'location' => $row['user_location'] ?? $snapshot['user_location'] ?? null,
                'concern' => $row['user_concern'] ?? $snapshot['user_concern'] ?? null,
                'lastMessage' => $row['last_message'] ?? $snapshot['last_message'] ?? null,
                'messageCount' => (int)($row['message_count'] ?? $snapshot['message_count'] ?? 0),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        echo json_encode([
            'success' => true,
            'items' => $items,
            'reasons' => $reasonLabels,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => max(1, (int)ceil($total / $limit)),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        error_log('TWC trash list error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to load trash']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = strtolower(trim((string)($input['action'] ?? 'trash')));
$conversationId = (int)($input['conversationId'] ?? $input['conversation_id'] ?? 0);
$trashId = (int)($input['trashId'] ?? $input['trash_id'] ?? 0);
$adminId = twc_trash_admin_id();
$adminName = twc_trash_admin_name();

if ($adminId === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Admin session is missing']);
    exit;
}

try {
    if ($action === 'trash') {
        $reason = strtolower(trim((string)($input['reason'] ?? '')));
        $details = trim((string)($input['details'] ?? ''));
        $reasons = twc_trash_reasons();

        if ($conversationId <= 0 || !array_key_exists($reason, $reasons)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Conversation and deletion reason are required']);
            exit;
        }
        if ($reason === 'other' && $details === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please describe the deletion reason']);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT conversation_id, user_id, user_name, user_email, user_phone,
                   user_location, user_concern, status, assigned_to, last_message,
                   last_message_time, created_at, updated_at
            FROM conversations
            WHERE conversation_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            exit;
        }

        $assignedTo = twc_safe_int($conversation['assigned_to'] ?? null);
        if ($assignedTo !== null && $assignedTo !== $adminId) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'This conversation is assigned to another admin']);
            exit;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM twc_conversation_trash WHERE conversation_id = ? LIMIT 1');
        $existingStmt->execute([$conversationId]);
        if ($existingStmt->fetchColumn()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'This conversation is already in Trash Bin']);
            exit;
        }

        $messageStmt = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ?');
        $messageStmt->execute([$conversationId]);
        $messageCount = (int)$messageStmt->fetchColumn();
        $snapshot = twc_trash_snapshot($conversation, $messageCount);
        $itemType = twc_conversation_item_type($conversation['user_concern'] ?? '');

        $insert = $pdo->prepare("
            INSERT INTO twc_conversation_trash
                (conversation_id, item_type, deletion_reason, deletion_details,
                 deleted_by_admin_id, deleted_by_admin_name, conversation_snapshot)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $conversationId,
            $itemType,
            $reason,
            $details !== '' ? $details : null,
            $adminId,
            $adminName,
            json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $trashId = (int)$pdo->lastInsertId();

        $closedStatus = twc_status_for_db($pdo, 'closed') ?? 'closed';
        $update = $pdo->prepare('UPDATE conversations SET status = ?, assigned_to = NULL, updated_at = NOW() WHERE conversation_id = ?');
        $update->execute([$closedStatus, $conversationId]);

        twc_log_deletion_action(
            $pdo,
            $conversationId,
            $trashId,
            'moved_to_trash',
            $itemType,
            $reason,
            $details !== '' ? $details : null,
            $adminId,
            $adminName,
            $snapshot
        );

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => ($itemType === 'general_enquiry' ? 'General enquiry' : 'Report') . ' moved to Trash Bin',
            'trashId' => $trashId,
        ]);
        exit;
    }

    if (!in_array($action, ['restore', 'delete_permanently'], true) || ($trashId <= 0 && $conversationId <= 0)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Valid trash action and item are required']);
        exit;
    }

    $pdo->beginTransaction();
    $lookupSql = $trashId > 0
        ? 'SELECT * FROM twc_conversation_trash WHERE id = ? FOR UPDATE'
        : 'SELECT * FROM twc_conversation_trash WHERE conversation_id = ? FOR UPDATE';
    $lookup = $pdo->prepare($lookupSql);
    $lookup->execute([$trashId > 0 ? $trashId : $conversationId]);
    $trash = $lookup->fetch(PDO::FETCH_ASSOC);

    if (!$trash) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trash item not found']);
        exit;
    }

    $trashId = (int)$trash['id'];
    $conversationId = (int)$trash['conversation_id'];
    $snapshot = json_decode((string)($trash['conversation_snapshot'] ?? ''), true);
    if (!is_array($snapshot)) {
        $snapshot = [];
    }

    if ($action === 'restore') {
        $status = twc_normalize_status_input($snapshot['workflow_status'] ?? 'open') ?? 'open';
        if (twc_is_closed_status($status)) {
            $status = 'open';
        }
        $dbStatus = twc_status_for_db($pdo, $status) ?? twc_status_for_db($pdo, 'open') ?? 'open';

        $restore = $pdo->prepare('UPDATE conversations SET status = ?, assigned_to = NULL, updated_at = NOW() WHERE conversation_id = ?');
        $restore->execute([$dbStatus, $conversationId]);
        if ($restore->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'The original conversation no longer exists']);
            exit;
        }

        twc_log_deletion_action(
            $pdo,
            $conversationId,
            $trashId,
            'restored',
            (string)$trash['item_type'],
            $trash['deletion_reason'],
            $trash['deletion_details'],
            $adminId,
            $adminName,
            $snapshot
        );
        $pdo->prepare('DELETE FROM twc_conversation_trash WHERE id = ?')->execute([$trashId]);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Conversation restored to Open']);
        exit;
    }

    twc_delete_conversation_content($pdo, $conversationId);
    twc_log_deletion_action(
        $pdo,
        $conversationId,
        $trashId,
        'permanently_deleted',
        (string)$trash['item_type'],
        $trash['deletion_reason'],
        $trash['deletion_details'],
        $adminId,
        $adminName,
        $snapshot
    );
    $pdo->prepare('DELETE FROM twc_conversation_trash WHERE id = ?')->execute([$trashId]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Conversation permanently deleted']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('TWC trash action error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Trash action failed']);
}
