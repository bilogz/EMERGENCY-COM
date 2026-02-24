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

$size = max(0, $size);
$rangeHeader = isset($_SERVER['HTTP_RANGE']) ? trim((string)$_SERVER['HTTP_RANGE']) : '';
$statusCode = 200;
$start = 0;
$end = $size > 0 ? ($size - 1) : 0;
$chunk = $data;

if ($size > 0 && $rangeHeader !== '' && preg_match('/^bytes=(\d*)-(\d*)$/i', $rangeHeader, $matches) === 1) {
    $requestedStart = $matches[1] !== '' ? (int)$matches[1] : null;
    $requestedEnd = $matches[2] !== '' ? (int)$matches[2] : null;

    if ($requestedStart === null && $requestedEnd !== null) {
        $suffixLength = min($size, max(0, $requestedEnd));
        $start = $suffixLength > 0 ? ($size - $suffixLength) : 0;
        $end = $size - 1;
    } else {
        $start = max(0, (int)($requestedStart ?? 0));
        $end = $requestedEnd !== null ? (int)$requestedEnd : ($size - 1);
    }

    if ($start >= $size || $end < $start) {
        http_response_code(416);
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes */' . $size);
        exit;
    }

    $end = min($size - 1, $end);
    $length = ($end - $start) + 1;
    $chunk = substr($data, $start, $length);
    if (!is_string($chunk)) {
        $chunk = '';
    }
    $statusCode = 206;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

http_response_code($statusCode);
header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
header('Content-Disposition: inline');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=604800, immutable');
if ($statusCode === 206) {
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}
header('Content-Length: ' . strlen($chunk));
echo $chunk;

