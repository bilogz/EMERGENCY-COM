<?php
/**
 * User Logout API
 * Handles user logout and session cleanup
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? 'guest';

// Include DB connection for activity logging
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    require_once '../../ADMIN/api/db_connect.php';
}

// Log logout activity if user is registered
if ($userType === 'registered' && $userId && isset($pdo)) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $activityStmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent, status, created_at)
            VALUES (?, 'logout', 'User logged out', ?, ?, 'success', NOW())
        ");
        $activityStmt->execute([$userId, $ipAddress, $userAgent]);
    } catch (PDOException $e) {
        error_log("Could not log logout activity: " . $e->getMessage());
    }
}

// Destroy session
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully."
]);
?>


