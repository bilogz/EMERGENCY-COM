<?php
/**
 * Global Alert Polling Endpoint
 * Returns the latest unread alert for the current user
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';

session_start();

// Current user identification (assuming citizen user_id or admin_user_id)
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // 1. Get user's subscribed categories
    $stmt = $pdo->prepare("SELECT category_id FROM user_subscriptions WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get user's barangay/role for targeting
    $uStmt = $pdo->prepare("SELECT barangay, user_type FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    $barangay = $user['barangay'] ?? '';
    $userType = $user['user_type'] ?? '';

    // 3. Find the latest alert that the user hasn't seen yet
    // Filter by: Subscription OR Targeted Barangay OR Targeted Role
    $sql = "SELECT a.*, c.name as category_name, c.icon as category_icon, c.color as category_color
            FROM alerts a
            LEFT JOIN alert_categories c ON a.category_id = c.id
            WHERE a.status = 'active'
            AND a.id NOT IN (SELECT alert_id FROM user_alert_marks WHERE user_id = ?)
            AND (
                a.category_id IN (" . (empty($subscriptions) ? "0" : implode(',', array_map('intval', $subscriptions))) . ")
                OR a.id IN (SELECT log_id FROM notification_logs WHERE recipients LIKE ?)
                OR a.id IN (SELECT log_id FROM notification_logs WHERE recipients LIKE ?)
            )
            ORDER BY a.created_at DESC
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId,
        "%$barangay%",
        "%$userType%"
    ]);
    
    $latestAlert = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Get unread count for badge
    $cStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM alerts a
        WHERE a.status = 'active'
        AND a.id NOT IN (SELECT alert_id FROM user_alert_marks WHERE user_id = ?)
        AND (
            a.category_id IN (" . (empty($subscriptions) ? "0" : implode(',', array_map('intval', $subscriptions))) . ")
            OR a.id IN (SELECT log_id FROM notification_logs WHERE recipients LIKE ?)
            OR a.id IN (SELECT log_id FROM notification_logs WHERE recipients LIKE ?)
        )
    ");
    $cStmt->execute([$userId, "%$barangay%", "%$userType%"]);
    $unreadCount = $cStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'alert' => $latestAlert,
        'unread_count' => (int)$unreadCount,
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
