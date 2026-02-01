<?php
/**
 * Admin Profile API
 * Fetch admin profile data and activity logs
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

// Include DB connection
require_once 'db_connect.php';

// Check if database connection is available
if (!$pdo) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection error. Please check your database settings."
    ]);
    exit();
}

$adminId = $_SESSION['admin_user_id'];
$action = $_GET['action'] ?? 'profile';

// Function to create tables if they don't exist
function ensureTablesExist($pdo) {
    try {
        // Create admin_activity_logs table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL COMMENT 'Action performed',
                description TEXT DEFAULT NULL COMMENT 'Detailed description',
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create admin_login_logs table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_login_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                login_status VARCHAR(20) NOT NULL COMMENT 'success, failed, blocked',
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                logout_at DATETIME DEFAULT NULL,
                session_duration INT DEFAULT NULL COMMENT 'Session duration in seconds',
                INDEX idx_admin_id (admin_id),
                INDEX idx_email (email),
                INDEX idx_login_status (login_status),
                INDEX idx_login_at (login_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_preferences (
                admin_id INT NOT NULL PRIMARY KEY,
                notification_sound VARCHAR(50) DEFAULT 'siren',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_updated_at (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log('Failed to create tables: ' . $e->getMessage());
        return false;
    }
}

// Ensure tables exist
if (!ensureTablesExist($pdo)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to initialize database tables."
    ]);
    exit();
}

try {
    switch ($action) {
        case 'profile':
            // Check if admin_user table exists
            $useAdminUserTable = false;
            try {
                $pdo->query("SELECT 1 FROM admin_user LIMIT 1");
                $useAdminUserTable = true;
            } catch (PDOException $e) {
                // admin_user table doesn't exist, use users table (backward compatibility)
            }
            
            // Get admin profile data
            if ($useAdminUserTable) {
                $stmt = $pdo->prepare("
                    SELECT id, user_id, name, username, email, phone, role, status, created_at, updated_at, last_login
                    FROM admin_user 
                    WHERE id = ?
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, name, email, user_type, status, created_at
                    FROM users 
                    WHERE id = ? AND user_type = 'admin'
                ");
            }
            $stmt->execute([$adminId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                echo json_encode(["success" => false, "message" => "Profile not found."]);
                exit();
            }
            
            // Get login statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_logins,
                    SUM(CASE WHEN login_status = 'success' THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN login_status = 'failed' THEN 1 ELSE 0 END) as failed_logins,
                    MAX(login_at) as last_login,
                    AVG(session_duration) as avg_session_duration
                FROM admin_login_logs
                WHERE admin_id = ?
            ");
            $stmt->execute([$adminId]);
            $loginStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get activity count by action type
            $stmt = $pdo->prepare("
                SELECT action, COUNT(*) as count
                FROM admin_activity_logs
                WHERE admin_id = ?
                GROUP BY action
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute([$adminId]);
            $activityStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "profile" => $profile,
                "login_stats" => $loginStats,
                "activity_stats" => $activityStats
            ]);
            break;

        case 'notification_sound_get':
            $stmt = $pdo->prepare("SELECT notification_sound FROM admin_preferences WHERE admin_id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $sound = $stmt->fetchColumn();
            if (!$sound) {
                $stmt = $pdo->prepare("INSERT INTO admin_preferences (admin_id, notification_sound) VALUES (?, 'siren')");
                $stmt->execute([$adminId]);
                $sound = 'siren';
            }
            echo json_encode([
                "success" => true,
                "notification_sound" => $sound
            ]);
            break;

        case 'notification_sound_set':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) $data = [];
            $sound = $data['notification_sound'] ?? '';
            $allowed = ['siren', 'beep', 'pulse', 'silent'];
            if (!in_array($sound, $allowed, true)) {
                echo json_encode(["success" => false, "message" => "Invalid notification sound."]);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO admin_preferences (admin_id, notification_sound) VALUES (?, ?) ON DUPLICATE KEY UPDATE notification_sound = VALUES(notification_sound)");
            $stmt->execute([$adminId, $sound]);
            echo json_encode([
                "success" => true,
                "notification_sound" => $sound
            ]);
            break;
            
        case 'activity_logs':
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;
            $filter = $_GET['filter'] ?? 'all'; // all, login, logout, notification, etc.
            
            // Build query with filter
            $whereClause = "WHERE admin_id = ?";
            $params = [$adminId];
            
            if ($filter !== 'all') {
                $whereClause .= " AND action = ?";
                $params[] = $filter;
            }
            
            // Get total count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM admin_activity_logs $whereClause");
            $stmt->execute($params);
            $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get activity logs
            $stmt = $pdo->prepare("
                SELECT id, action, description, ip_address, user_agent, created_at
                FROM admin_activity_logs
                $whereClause
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "activities" => $activities,
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total" => $totalCount,
                    "total_pages" => ceil($totalCount / $limit)
                ]
            ]);
            break;
            
        case 'login_logs':
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM admin_login_logs WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get login logs
            $stmt = $pdo->prepare("
                SELECT id, email, login_status, ip_address, user_agent, login_at, logout_at, session_duration
                FROM admin_login_logs
                WHERE admin_id = ?
                ORDER BY login_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$adminId, $limit, $offset]);
            $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "logins" => $logins,
                "pagination" => [
                    "page" => $page,
                    "limit" => $limit,
                    "total" => $totalCount,
                    "total_pages" => ceil($totalCount / $limit)
                ]
            ]);
            break;
            
        default:
            echo json_encode(["success" => false, "message" => "Invalid action."]);
            break;
    }
} catch (PDOException $e) {
    error_log('Profile API Error: ' . $e->getMessage());
    
    // Provide more specific error messages in development
    $errorMessage = "Database error occurred.";
    
    // Check for specific error types
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $errorMessage = "Required database tables are missing. Please run the setup SQL script.";
    } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
        $errorMessage = "Cannot connect to database. Please check if MySQL is running.";
    } elseif (strpos($e->getMessage(), "Access denied") !== false) {
        $errorMessage = "Database access denied. Please check credentials.";
    }
    
    echo json_encode([
        "success" => false,
        "message" => $errorMessage,
        "error_details" => $e->getMessage() // For debugging (remove in production)
    ]);
}

