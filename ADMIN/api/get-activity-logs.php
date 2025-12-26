<?php
/**
 * Get Admin Activity Logs
 * Returns activity logs for the logged-in admin
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

require_once 'db_connect.php';

try {
    $adminId = $_SESSION['admin_user_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Get activity logs
    $stmt = $pdo->prepare("
        SELECT id, action, description, ip_address, user_agent, created_at
        FROM admin_activity_logs
        WHERE admin_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$adminId, $limit, $offset]);
    $logs = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_activity_logs WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        "success" => true,
        "logs" => $logs,
        "total" => $total,
        "limit" => $limit,
        "offset" => $offset
    ]);
    
} catch (PDOException $e) {
    error_log("Get Activity Logs Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred"]);
}



