<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';
/** @var PDO $pdo */

$user_id  = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$role     = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';

try {
    if ($role === 'admin' || $role === 'responder') {
        // Admins see all conversations
        $stmt = $pdo->prepare('SELECT * FROM conversations ORDER BY updated_at DESC');
        $stmt->execute();
    } else {
        if (empty($user_id)) {
            apiResponse::error('user_id is required for non-admin users', 400);
        }
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE user_id = ? ORDER BY updated_at DESC');
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
