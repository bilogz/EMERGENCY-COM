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
    // Assumes you have a table that stores active polls; adjust table/columns to your schema.
    // If you don't have polls yet, just always return null.
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
} catch (PDOException $e) {
    error_log('Active poll DB error: ' . $e->getMessage());
    apiResponse::error('Database error.', 500);
}
