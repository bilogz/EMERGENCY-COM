<?php
/**
 * Chat attachment stream endpoint (PostgreSQL-backed).
 * Usage: /USERS/api/chat-attachment.php?id=<attachment_id>
 */

header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.env.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

$attachmentId = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($attachmentId === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Attachment id is required']);
    exit;
}

$attachment = twc_fetch_attachment_from_postgres($attachmentId);
if (!$attachment || !isset($attachment['data'])) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Attachment not found']);
    exit;
}

$mime = trim((string)($attachment['mime'] ?? 'application/octet-stream'));
$data = $attachment['data'];
$size = isset($attachment['size']) ? (int)$attachment['size'] : (is_string($data) ? strlen($data) : 0);

if (!is_string($data) || $data === '') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Attachment payload is empty']);
    exit;
}

header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
header('Content-Length: ' . (string)max(0, $size));
header('Cache-Control: public, max-age=604800, immutable');
echo $data;

