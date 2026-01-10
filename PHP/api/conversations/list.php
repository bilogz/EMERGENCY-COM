<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';

$user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$role     = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';

try {
    if ($role === 'admin' || $role === 'responder') {
        $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations ORDER BY created_at DESC');
        $stmt->execute();
    } else {
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'user_id is required for non-admin users']);
            exit();
        }
        $stmt = $pdo->prepare('SELECT id, user_id, created_at FROM conversations WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'message' => 'OK', 'conversations' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Conversations list error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>

