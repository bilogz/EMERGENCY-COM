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
require_once __DIR__ . '/../services/AdminService.php';

try {
    $adminId = $_SESSION['admin_user_id'];
    $currentLoginLogId = $_SESSION['admin_login_log_id'] ?? null;
    
    $adminService = new AdminService($pdo);
    $profileData = $adminService->getCompleteProfile($adminId, $currentLoginLogId);
    
    if (!$profileData) {
        echo json_encode(["success" => false, "message" => "Admin not found"]);
        exit();
    }
    
    echo json_encode([
        "success" => true,
        "profile" => $profileData['profile'],
        "current_login" => $profileData['current_login'],
        "last_login" => $profileData['last_login']
    ]);
    
} catch (PDOException $e) {
    error_log("Get Admin Profile Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred"]);
}








