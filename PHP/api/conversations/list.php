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
    
    // Ensure numeric fields are returned as integers for Kotlin compatibility
    foreach ($rows as &$row) {
        if (isset($row['conversation_id'])) $row['conversation_id'] = (int)$row['conversation_id'];
        if (isset($row['is_guest'])) $row['is_guest'] = (int)$row['is_guest'];
        if (isset($row['assigned_to'])) $row['assigned_to'] = $row['assigned_to'] !== null ? (int)$row['assigned_to'] : null;
    }

    apiResponse::success(['conversations' => $rows], 'OK');
} catch (PDOException $e) {
    error_log('Conversations list DB error: ' . $e->getMessage());
    apiResponse::error('A database error occurred: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Conversations list error: ' . $e->getMessage());
    apiResponse::error('An unexpected error occurred: ' . $e->getMessage(), 500);
}
?>
