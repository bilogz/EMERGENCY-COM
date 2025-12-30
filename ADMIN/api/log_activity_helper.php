<?php
/**
 * Activity Logging Helper for Admin Actions
 * Include this in any admin page that needs to log activities
 * 
 * Usage:
 * require_once '../api/log_activity_helper.php';
 * logActivity('action_name', 'Description of the action');
 */

session_start();
require_once 'activity_logger.php';

/**
 * Simplified activity logging function
 * Automatically uses the current admin's session
 * 
 * @param string $action Action type (e.g., 'send_notification', 'update_user', 'delete_subscriber')
 * @param string|null $description Optional description
 * @return bool Success status
 */
function logActivity($action, $description = null) {
    if (!isset($_SESSION['admin_user_id'])) {
        error_log('Log Activity: No admin user ID in session');
        return false;
    }
    
    $adminId = $_SESSION['admin_user_id'];
    return logAdminActivity($adminId, $action, $description);
}

/**
 * Common action types for consistency:
 * 
 * LOGIN/LOGOUT:
 * - 'login' - Admin logged in
 * - 'logout' - Admin logged out
 * - 'login_failed' - Failed login attempt
 * 
 * NOTIFICATIONS:
 * - 'send_notification' - Sent mass notification
 * - 'schedule_notification' - Scheduled a notification
 * - 'cancel_notification' - Cancelled a notification
 * 
 * USER MANAGEMENT:
 * - 'create_user' - Created new user/subscriber
 * - 'update_user' - Updated user details
 * - 'delete_user' - Deleted user
 * - 'approve_admin' - Approved admin account
 * - 'reject_admin' - Rejected admin account
 * 
 * SUBSCRIBERS:
 * - 'add_subscriber' - Added new subscriber
 * - 'update_subscriber' - Updated subscriber
 * - 'delete_subscriber' - Deleted subscriber
 * 
 * SYSTEM:
 * - 'update_settings' - Updated system settings
 * - 'view_reports' - Viewed reports
 * - 'export_data' - Exported data
 * 
 * COMMUNICATIONS:
 * - 'send_sms' - Sent SMS
 * - 'send_email' - Sent email
 * - 'reply_message' - Replied to citizen message
 * - 'broadcast_alert' - Broadcast emergency alert
 */










