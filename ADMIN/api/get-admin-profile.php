<?php
/**
 * Get Admin Profile Data
 * Returns admin account details and current login status
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
    
    // Get admin details
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone, status, user_type, created_at, updated_at
        FROM users
        WHERE id = ? AND user_type = 'admin'
    ");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode(["success" => false, "message" => "Admin not found"]);
        exit();
    }
    
    // Get current login session info
    $currentLoginLogId = $_SESSION['admin_login_log_id'] ?? null;
    $currentLoginInfo = null;
    
    if ($currentLoginLogId) {
        $stmt = $pdo->prepare("
            SELECT login_at, ip_address, user_agent
            FROM admin_login_logs
            WHERE id = ? AND admin_id = ?
        ");
        $stmt->execute([$currentLoginLogId, $adminId]);
        $currentLoginInfo = $stmt->fetch();
    }
    
    // Get last login info
    $stmt = $pdo->prepare("
        SELECT login_at, ip_address, logout_at, session_duration
        FROM admin_login_logs
        WHERE admin_id = ? AND login_status = 'success'
        ORDER BY login_at DESC
        LIMIT 1, 1
    ");
    $stmt->execute([$adminId]);
    $lastLogin = $stmt->fetch();
    
    echo json_encode([
        "success" => true,
        "profile" => [
            "id" => $admin['id'],
            "name" => $admin['name'],
            "email" => $admin['email'],
            "phone" => $admin['phone'],
            "status" => $admin['status'],
            "user_type" => $admin['user_type'],
            "created_at" => $admin['created_at'],
            "updated_at" => $admin['updated_at']
        ],
        "current_login" => $currentLoginInfo ? [
            "login_at" => $currentLoginInfo['login_at'],
            "ip_address" => $currentLoginInfo['ip_address'],
            "user_agent" => $currentLoginInfo['user_agent']
        ] : null,
        "last_login" => $lastLogin ? [
            "login_at" => $lastLogin['login_at'],
            "ip_address" => $lastLogin['ip_address'],
            "logout_at" => $lastLogin['logout_at'],
            "session_duration" => $lastLogin['session_duration']
        ] : null
    ]);
    
} catch (PDOException $e) {
    error_log("Get Admin Profile Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred"]);
}






