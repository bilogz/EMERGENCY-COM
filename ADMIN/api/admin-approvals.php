<?php
/**
 * Admin Approval Management API
 * Handles listing, approving, and rejecting pending admin accounts
 */

session_start();
header('Content-Type: application/json');

// Include DB connection and security helpers
require_once 'db_connect.php';
require_once 'security-helpers.php';
require_once 'activity_logger.php';

// Helper function to get client IP (if not already defined)
if (!function_exists('getClientIP')) {
    function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit();
}

// Verify admin status - check admin_user table
try {
    $adminId = $_SESSION['admin_user_id'] ?? null;
    
    // Check if admin_user table exists
    $useAdminUserTable = false;
    try {
        $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
        $useAdminUserTable = true;
    } catch (PDOException $e) {
        // admin_user table doesn't exist, use users table (backward compatibility)
    }
    
    if ($useAdminUserTable) {
        $stmt = $pdo->prepare("SELECT id, role, status FROM admin_user WHERE id = ? AND status = 'active'");
    } else {
        $stmt = $pdo->prepare("SELECT id, user_type, status FROM users WHERE id = ? AND user_type = 'admin' AND status = 'active'");
    }
    
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode(["success" => false, "message" => "Unauthorized. Invalid admin session."]);
        exit();
    }
} catch (PDOException $e) {
    error_log("Admin verification error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // Check if admin_user table exists
    $useAdminUserTable = false;
    try {
        $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
        $useAdminUserTable = true;
    } catch (PDOException $e) {
        // admin_user table doesn't exist, use users table (backward compatibility)
    }
    
    if ($method === 'GET' && $action === 'list') {
        // List all pending admin approvals
        if ($useAdminUserTable) {
            $stmt = $pdo->prepare("
                SELECT id, name, email, role, status, created_at, updated_at 
                FROM admin_user 
                WHERE status = 'pending_approval' 
                ORDER BY created_at DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, email, status, created_at, updated_at 
                FROM users 
                WHERE user_type = 'admin' AND status = 'pending_approval' 
                ORDER BY created_at DESC
            ");
        }
        $stmt->execute();
        $pendingAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($pendingAdmins as &$admin) {
            $admin['created_at'] = date('Y-m-d H:i:s', strtotime($admin['created_at']));
            $admin['updated_at'] = date('Y-m-d H:i:s', strtotime($admin['updated_at']));
        }
        
        echo json_encode([
            "success" => true,
            "data" => $pendingAdmins,
            "count" => count($pendingAdmins)
        ]);
        
    } elseif ($method === 'POST' && $action === 'approve') {
        // Approve a pending admin account
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $approvalAction = $input['action'] ?? 'approve'; // 'approve' or 'reject'
        
        if (!$userId) {
            echo json_encode(["success" => false, "message" => "User ID is required."]);
            exit();
        }
        
        // Verify the user exists and is pending approval
        if ($useAdminUserTable) {
            $stmt = $pdo->prepare("SELECT id, name, email, status FROM admin_user WHERE id = ? AND status = 'pending_approval'");
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, status FROM users WHERE id = ? AND user_type = 'admin' AND status = 'pending_approval'");
        }
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(["success" => false, "message" => "User not found or already processed."]);
            exit();
        }
        
        // Prevent self-approval (though this shouldn't happen)
        if ($userId == $adminId) {
            echo json_encode(["success" => false, "message" => "You cannot approve your own account."]);
            exit();
        }
        
        if ($approvalAction === 'approve') {
            // Approve the account
            if ($useAdminUserTable) {
                // Get user_id first
                $getUserStmt = $pdo->prepare("SELECT user_id FROM admin_user WHERE id = ?");
                $getUserStmt->execute([$userId]);
                $adminUser = $getUserStmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE admin_user SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Also update users table if linked
                if ($adminUser && $adminUser['user_id']) {
                    $updateUserStmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
                    $updateUserStmt->execute([$adminUser['user_id']]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            }
            
            // Log the approval action
            logAdminActivity($adminId, 'approve_admin', "Approved admin account: {$user['name']} ({$user['email']})");
            
            echo json_encode([
                "success" => true,
                "message" => "Admin account approved successfully. {$user['name']} ({$user['email']}) can now log in.",
                "action" => "approved"
            ]);
        } elseif ($approvalAction === 'reject') {
            // Reject the account (set to inactive)
            if ($useAdminUserTable) {
                // Get user_id first
                $getUserStmt = $pdo->prepare("SELECT user_id FROM admin_user WHERE id = ?");
                $getUserStmt->execute([$userId]);
                $adminUser = $getUserStmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE admin_user SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
                
                // Also update users table if linked
                if ($adminUser && $adminUser['user_id']) {
                    $updateUserStmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                    $updateUserStmt->execute([$adminUser['user_id']]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            }
            
            // Log the rejection action
            logAdminActivity($adminId, 'reject_admin', "Rejected admin account: {$user['name']} ({$user['email']})");
            
            echo json_encode([
                "success" => true,
                "message" => "Admin account rejected. {$user['name']} ({$user['email']}) will not be able to log in.",
                "action" => "rejected"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action. Use 'approve' or 'reject'."]);
        }
        
    } elseif ($method === 'GET' && $action === 'stats') {
        // Get statistics about pending approvals
        if ($useAdminUserTable) {
            $pendingCount = $pdo->query("SELECT COUNT(*) FROM admin_user WHERE status = 'pending_approval'")->fetchColumn();
            $activeCount = $pdo->query("SELECT COUNT(*) FROM admin_user WHERE status = 'active'")->fetchColumn();
            $inactiveCount = $pdo->query("SELECT COUNT(*) FROM admin_user WHERE status = 'inactive'")->fetchColumn();
        } else {
            $pendingCount = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND status = 'pending_approval'")->fetchColumn();
            $activeCount = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND status = 'active'")->fetchColumn();
            $inactiveCount = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND status = 'inactive'")->fetchColumn();
        }
        
        echo json_encode([
            "success" => true,
            "stats" => [
                "pending" => (int)$pendingCount,
                "active" => (int)$activeCount,
                "inactive" => (int)$inactiveCount
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request."]);
    }
    
} catch (PDOException $e) {
    error_log("Admin approval API error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again."
    ]);
} catch (Exception $e) {
    error_log("Admin approval API general error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "An error occurred. Please try again."
    ]);
}

?>

