<?php
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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

$conversationId = (int)($input['conversationId'] ?? 0);
$action = strtolower(trim((string)($input['action'] ?? 'claim')));
$adminId = twc_safe_int($_SESSION['admin_user_id'] ?? null);
$adminName = $_SESSION['admin_username'] ?? $_SESSION['admin_name'] ?? 'Administrator';

if ($conversationId <= 0 || $adminId === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing conversation or admin session']);
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS twc_assignment_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            action VARCHAR(40) NOT NULL,
            admin_id INT NULL,
            admin_name VARCHAR(255) NULL,
            previous_admin_id INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation_id (conversation_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT conversation_id, status, assigned_to FROM conversations WHERE conversation_id = ? FOR UPDATE");
    $stmt->execute([$conversationId]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$conv) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    $assignedTo = twc_safe_int($conv['assigned_to'] ?? null);

    if ($action === 'release') {
        if ($assignedTo !== null && $assignedTo !== $adminId) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This report is assigned to another admin']);
            exit;
        }
        $status = twc_status_for_db($pdo, 'open');
        $pdo->prepare("UPDATE conversations SET assigned_to = NULL, status = ?, updated_at = NOW() WHERE conversation_id = ?")
            ->execute([$status, $conversationId]);
        $pdo->prepare("INSERT INTO twc_assignment_audit (conversation_id, action, admin_id, admin_name, previous_admin_id) VALUES (?, 'released', ?, ?, ?)")
            ->execute([$conversationId, $adminId, $adminName, $assignedTo]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Report released for another admin', 'assignedTo' => null]);
        exit;
    }

    if ($assignedTo !== null && $assignedTo !== $adminId) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'locked' => true,
            'message' => 'This report is already being handled by another admin',
            'assignedTo' => $assignedTo,
        ]);
        exit;
    }

    $status = twc_status_for_db($pdo, 'in_progress');
    $pdo->prepare("UPDATE conversations SET assigned_to = ?, status = ?, updated_at = NOW() WHERE conversation_id = ?")
        ->execute([$adminId, $status, $conversationId]);
    $pdo->prepare("INSERT INTO twc_assignment_audit (conversation_id, action, admin_id, admin_name, previous_admin_id) VALUES (?, 'claimed', ?, ?, ?)")
        ->execute([$conversationId, $adminId, $adminName, $assignedTo]);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Report assigned to you',
        'assignedTo' => $adminId,
        'adminName' => $adminName,
    ]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Chat claim error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update report assignment']);
}
