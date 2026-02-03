<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Placeholder endpoint for integrating with an external dispatch/response system.
// Intentionally does not send anything externally yet.

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$callId = $input['callId'] ?? null;
$emergencyType = $input['emergencyType'] ?? null;

if (!$callId || !$emergencyType) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing callId or emergencyType']);
    exit;
}

// Future integration point:
// - send payload to external responder system
// - store dispatch record in DB

$response = [
    'success' => true,
    'message' => 'Dispatch queued (placeholder).',
    'data' => [
        'callId' => $callId,
        'emergencyType' => $emergencyType,
        'adminId' => $_SESSION['admin_user_id'] ?? null,
        'adminUsername' => $_SESSION['admin_username'] ?? null,
        'caller' => $input['caller'] ?? null,
        'location' => $input['location'] ?? null,
        'conversationId' => $input['conversationId'] ?? null,
        'timestamp' => time()
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
