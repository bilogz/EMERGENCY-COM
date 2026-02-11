<?php
/**
 * Update Chat Status API (Admin)
 * Supports workflow statuses: open, in_progress, waiting_user, resolved, closed.
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
    $conversationId = (int)($input['conversationId'] ?? $_POST['conversationId'] ?? 0);
    $requestedStatus = (string)($input['status'] ?? $_POST['status'] ?? '');
    $normalizedStatus = twc_normalize_status_input($requestedStatus);
    $targetStatus = $normalizedStatus !== null ? twc_status_for_db($pdo, $normalizedStatus) : null;

    if ($conversationId <= 0 || $normalizedStatus === null || $targetStatus === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID and valid status are required']);
        exit;
    }

    $adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';
    $adminId = twc_safe_int($_SESSION['admin_user_id'] ?? null);

    $pdo->beginTransaction();

    $setParts = ["status = ?", "updated_at = NOW()"];
    $params = [$targetStatus];

    if (twc_is_closed_status($normalizedStatus)) {
        $setParts[] = "last_message = CONCAT('Closed by ', ?)";
        $params[] = $adminName;
    } elseif ($normalizedStatus === 'open' || $normalizedStatus === 'active') {
        $setParts[] = "last_message = CONCAT('Re-opened by ', ?)";
        $params[] = $adminName;
    }

    if ($adminId !== null && twc_column_exists($pdo, 'conversations', 'assigned_to')) {
        $setParts[] = "assigned_to = ?";
        $params[] = $adminId;
    }

    $params[] = $conversationId;
    $sql = "UPDATE conversations SET " . implode(', ', $setParts) . " WHERE conversation_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    if (twc_table_exists($pdo, 'chat_queue')) {
        try {
            $queueStatus = twc_is_closed_status($normalizedStatus) ? 'closed' : 'accepted';
            $queueSql = "UPDATE chat_queue SET status = ?, updated_at = NOW()";
            $queueParams = [$queueStatus];
            if ($adminId !== null && twc_column_exists($pdo, 'chat_queue', 'assigned_to')) {
                $queueSql .= ", assigned_to = ?";
                $queueParams[] = $adminId;
            }
            $queueSql .= " WHERE conversation_id = ?";
            $queueParams[] = $conversationId;
            $queueStmt = $pdo->prepare($queueSql);
            $queueStmt->execute($queueParams);
        } catch (Throwable $e) {
            error_log('Chat queue status update warning: ' . $e->getMessage());
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Conversation status updated successfully',
        'newStatus' => twc_ui_conversation_status($targetStatus),
        'workflowStatus' => $targetStatus,
    ]);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin chat update status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update conversation status']);
}
