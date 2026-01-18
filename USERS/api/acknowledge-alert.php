<?php
/**
 * Acknowledge Alert Endpoint
 * Marks an alert as seen by the user
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../ADMIN/api/db_connect.php';

session_start();

$userId = $_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? null;
$alertId = $_POST['alert_id'] ?? null;

if (!$userId || !$alertId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_alert_marks (user_id, alert_id) VALUES (?, ?)");
    $stmt->execute([$userId, $alertId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
