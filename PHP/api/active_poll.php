<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/apiResponse.php';

/** @var PDO $pdo */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    apiResponse::error("Method Not Allowed. Use GET.", 405);
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    apiResponse::success(['poll' => null], 'No active poll.');
}

try {
    // Only query if the polls table exists; otherwise just return null.
    $tableExistsStmt = $pdo->prepare("SHOW TABLES LIKE 'polls'");
    $tableExistsStmt->execute();
    $tableExists = (bool)$tableExistsStmt->fetchColumn();

    if (!$tableExists) {
        apiResponse::success(['poll' => null], 'No active poll.');
    }

    $stmt = $pdo->prepare("
        SELECT id, title, message, created_at
        FROM polls
        WHERE is_active = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute();

    $poll = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    apiResponse::success(['poll' => $poll], $poll ? 'Active poll found.' : 'No active poll.');
} catch (Throwable $e) {
    // Never break the app over polls; log and return safe null.
    error_log('Active poll error: ' . $e->getMessage());
    apiResponse::success(['poll' => null], 'No active poll.');
}
