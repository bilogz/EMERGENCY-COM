<?php
/**
 * Manual incident priority override for Citizen Reports.
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
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $conversationId = (int)($input['conversationId'] ?? $_POST['conversationId'] ?? 0);
    $level = strtolower(trim((string)($input['priority'] ?? $_POST['priority'] ?? '')));

    $scoreByLevel = [
        'critical' => 90,
        'high' => 70,
        'urgent' => 45,
        'moderate' => 20,
        'low' => 0,
    ];

    if ($conversationId <= 0 || !array_key_exists($level, $scoreByLevel)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Conversation ID and valid priority are required']);
        exit;
    }

    twc_ensure_incident_priority_columns($pdo);
    $priority = twc_incident_priority_from_score($scoreByLevel[$level]);

    $stmt = $pdo->prepare("
        UPDATE conversations
        SET incident_priority_score = ?,
            incident_priority_level = ?,
            incident_priority_color = ?,
            incident_priority_manual = 1,
            updated_at = NOW()
        WHERE conversation_id = ?
    ");
    $stmt->execute([
        $priority['score'],
        $priority['priority'],
        $priority['color'],
        $conversationId,
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'incidentPriority' => $priority + ['manual' => true],
    ]);
} catch (Throwable $e) {
    error_log('Incident priority override error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update incident priority']);
}
