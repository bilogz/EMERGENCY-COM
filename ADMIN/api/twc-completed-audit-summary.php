<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/chat-logic.php';

try {
    $limit = 10;
    twc_ensure_conversation_trash_storage($pdo);

    $stmt = $pdo->prepare("
        SELECT id, call_id, conversation_id, emergency_type, caller_name, caller_phone,
               status, response_status, response_status_note, requested_by, created_at
        FROM transfer_call_audit
        WHERE status = 'completed' OR response_status = 'completed'
        ORDER BY COALESCE(status_updated_at, created_at) DESC, id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, conversation_id, action, admin_id, admin_name, previous_admin_id, created_at
        FROM twc_assignment_audit
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, conversation_id, trash_id, action, item_type, deletion_reason,
               deletion_details, admin_id, admin_name, item_name, item_location,
               last_message, ip_address, created_at
        FROM twc_conversation_deletion_audit
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $deletions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'transfers' => $transfers,
        'assignments' => $assignments,
        'deletions' => $deletions,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Completed communication audit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load completed communication audit']);
}
