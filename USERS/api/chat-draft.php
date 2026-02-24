<?php
/**
 * Chat draft cache API (PostgreSQL-backed).
 *
 * Actions:
 * - load (GET/POST): returns cached draft
 * - save (POST): saves or updates draft text
 * - clear (POST): deletes draft cache
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.env.php';
require_once __DIR__ . '/../../ADMIN/api/chat-logic.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = [];
$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
if ($method === 'POST' && stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$request = array_merge($_GET, $_POST, $input);
$action = strtolower(trim((string)($request['action'] ?? 'load')));
$userId = trim((string)($request['userId'] ?? ''));
$conversationIdRaw = $request['conversationId'] ?? null;
$conversationId = trim((string)$conversationIdRaw);
if ($conversationId === '' || strcasecmp($conversationId, 'null') === 0 || strcasecmp($conversationId, 'undefined') === 0) {
    $conversationId = null;
}

if ($userId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'userId is required']);
    exit;
}

if ($action === 'load' || $action === 'get' || $action === 'fetch') {
    $draft = twc_postgres_chat_draft_fetch($userId, $conversationId);
    echo json_encode([
        'success' => true,
        'draftText' => $draft ? (string)($draft['text'] ?? '') : '',
        'scope' => $draft ? (string)($draft['scope'] ?? '') : '',
        'updatedAt' => $draft ? (string)($draft['updatedAt'] ?? '') : '',
        'storage' => $draft ? 'postgres' : 'local-fallback',
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required for this action']);
    exit;
}

if ($action === 'save' || $action === 'upsert') {
    $draftText = (string)($request['text'] ?? $request['draftText'] ?? '');
    $trimmed = trim($draftText);
    if ($trimmed === '') {
        $cleared = twc_postgres_chat_draft_clear($userId, $conversationId, true);
        echo json_encode([
            'success' => true,
            'stored' => false,
            'cleared' => $cleared,
            'storage' => $cleared ? 'postgres' : 'local-fallback',
        ]);
        exit;
    }

    $stored = twc_postgres_chat_draft_store($userId, $conversationId, $draftText);
    echo json_encode([
        'success' => true,
        'stored' => $stored,
        'storage' => $stored ? 'postgres' : 'local-fallback',
    ]);
    exit;
}

if ($action === 'clear' || $action === 'delete') {
    $includePendingRaw = strtolower(trim((string)($request['includePending'] ?? '1')));
    $includePending = in_array($includePendingRaw, ['1', 'true', 'yes', 'on'], true);
    $cleared = twc_postgres_chat_draft_clear($userId, $conversationId, $includePending);
    echo json_encode([
        'success' => true,
        'cleared' => $cleared,
        'storage' => $cleared ? 'postgres' : 'local-fallback',
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unsupported action']);

