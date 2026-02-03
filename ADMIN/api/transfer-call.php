<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Placeholder endpoint for transferring a call to a response team while keeping the admin in listen mode.
// Real implementation would require SIP/RTC infrastructure or a media server.

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
if (!$callId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing callId']);
    exit;
}

// Future integration point:
// - create a transfer request to response team
// - establish a 3-way call or supervised transfer with a media server

echo json_encode([
    'success' => true,
    'message' => 'Transfer initiated (placeholder).',
    'data' => [
        'callId' => $callId,
        'adminId' => $_SESSION['admin_user_id'] ?? null,
        'adminUsername' => $_SESSION['admin_username'] ?? null,
        'emergencyType' => $input['emergencyType'] ?? null,
        'caller' => $input['caller'] ?? null,
        'location' => $input['location'] ?? null,
        'conversationId' => $input['conversationId'] ?? null,
        'timestamp' => time()
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
