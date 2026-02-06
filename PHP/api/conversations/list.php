<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$role     = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';

try {
    if ($role === 'admin' || $role === 'responder') {
        $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations ORDER BY created_at DESC');
        $stmt->execute();
    } else {
        if ($user_id <= 0) {
            apiResponse::error('user_id is required for non-admin users', 400);
        }
        $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    apiResponse::success(['conversations' => $rows], 'OK');
} catch (PDOException $e) {
    error_log('Conversations list DB error: ' . $e->getMessage());
    apiResponse::error('A database error occurred.', 500, $e->getMessage());
} catch (Exception $e) {
    error_log('Conversations list error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred.', 500, $e->getMessage());
}
?>

