<?php
/**
 * Admin Logout Handler
 * Clears session and redirects to login page
 */

session_start();

// Log logout activity if logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    require_once 'api/activity_logger.php';
    
    $adminId = $_SESSION['admin_user_id'] ?? null;
    $loginLogId = $_SESSION['admin_login_log_id'] ?? null;
    
    if ($adminId) {
        logAdminActivity($adminId, 'logout', 'Admin logged out');
    }
    
    if ($loginLogId && $adminId) {
        updateLoginLogout($loginLogId);
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Clear any admin-related localStorage data (client-side will handle this)
// Redirect to login page
header('Location: login.php');
exit();
?>

