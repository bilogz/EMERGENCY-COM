<?php
/**
 * Get Admin Login Logs
 * Returns login history for the logged-in admin
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
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Get login logs
    $stmt = $pdo->prepare("
        SELECT id, email, login_status, ip_address, user_agent, login_at, logout_at, session_duration
        FROM admin_login_logs
        WHERE admin_id = ?
        ORDER BY login_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$adminId, $limit, $offset]);
    $logs = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_login_logs WHERE admin_id = ?");
    $stmt->execute([$adminId]);
    $total = $stmt->fetchColumn();
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_logins,
            SUM(CASE WHEN login_status = 'success' THEN 1 ELSE 0 END) as successful_logins,
            SUM(CASE WHEN login_status = 'failed' THEN 1 ELSE 0 END) as failed_logins,
            MAX(login_at) as last_login
        FROM admin_login_logs
        WHERE admin_id = ?
    ");
    $stmt->execute([$adminId]);
    $stats = $stmt->fetch();
    
    echo json_encode([
        "success" => true,
        "logs" => $logs,
        "statistics" => $stats,
        "total" => $total,
        "limit" => $limit,
        "offset" => $offset
    ]);
    
} catch (PDOException $e) {
    error_log("Get Login Logs Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred"]);
}




