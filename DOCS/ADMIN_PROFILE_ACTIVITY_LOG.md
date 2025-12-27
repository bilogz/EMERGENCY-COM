# Admin Profile & Activity Logging System

## Overview
The Emergency Communication System now includes a comprehensive admin profile management system with detailed activity logging to track all admin actions and monitor account usage.

## Features

### 1. Admin Profile Page
Located at: `sidebar/profile.php`

The profile page displays:
- **Account Information**
  - Profile avatar (dynamically generated)
  - Admin name
  - Email address
  - Account status
  - Account creation date
  - Last login timestamp

- **Statistics Dashboard**
  - Total logins
  - Successful logins
  - Average session duration
  - Top 5 activities performed

- **Activity Log Tab**
  - Comprehensive list of all admin actions
  - Filter by action type (login, logout, notifications, user management, etc.)
  - Pagination support (20 records per page)
  - Details include: timestamp, action, description, IP address

- **Login History Tab**
  - Complete login/logout history
  - Session duration tracking
  - Success/failed login attempts
  - IP address logging
  - Device/browser information (user agent)

### 2. Activity Logging System

#### Database Tables

**admin_activity_logs** - Tracks all admin actions
```sql
- id (Primary Key)
- admin_id (Foreign Key to users table)
- action (varchar) - Type of action performed
- description (text) - Detailed description
- ip_address (varchar) - IP address of admin
- user_agent (text) - Browser/device information
- created_at (datetime) - Timestamp
```

**admin_login_logs** - Tracks login/logout sessions
```sql
- id (Primary Key)
- admin_id (Foreign Key to users table)
- email (varchar) - Email used for login
- login_status (varchar) - success, failed, blocked
- ip_address (varchar) - IP address
- user_agent (text) - Browser/device information
- login_at (datetime) - Login timestamp
- logout_at (datetime) - Logout timestamp (nullable)
- session_duration (int) - Duration in seconds (nullable)
```

#### Activity Types
The system logs the following activities:

**Authentication:**
- `login` - Successful login
- `logout` - Admin logout
- `login_failed` - Failed login attempt

**Admin Management:**
- `approve_admin` - Approved admin account
- `reject_admin` - Rejected admin account

**User Management:**
- `create_user` - Created new user
- `update_user` - Updated user details
- `delete_user` - Deleted user

**Notifications:**
- `send_notification` - Sent mass notification
- `schedule_notification` - Scheduled notification
- `cancel_notification` - Cancelled notification

**Communications:**
- `send_sms` - Sent SMS message
- `send_email` - Sent email
- `reply_message` - Replied to citizen message
- `broadcast_alert` - Broadcast emergency alert

**System:**
- `update_settings` - Updated system settings
- `view_reports` - Viewed reports
- `export_data` - Exported data

### 3. Implementation Details

#### API Endpoints

**Profile API** (`api/profile.php`)
- `GET ?action=profile` - Get admin profile and statistics
- `GET ?action=activity_logs&page=1&limit=20&filter=all` - Get activity logs
- `GET ?action=login_logs&page=1&limit=20` - Get login history

#### How to Log Activities

**Method 1: Using activity_logger.php directly**
```php
require_once 'api/activity_logger.php';

$adminId = $_SESSION['admin_user_id'];
logAdminActivity($adminId, 'send_notification', 'Sent emergency alert to 500 subscribers');
```

**Method 2: Using the simplified helper**
```php
require_once 'api/log_activity_helper.php';

// Automatically uses current admin's session
logActivity('send_notification', 'Sent emergency alert to 500 subscribers');
```

#### Session Variables
When an admin logs in, the following session variables are set:
- `$_SESSION['admin_logged_in']` - Boolean flag
- `$_SESSION['admin_user_id']` - Admin's user ID
- `$_SESSION['admin_username']` - Admin's display name
- `$_SESSION['admin_email']` - Admin's email address
- `$_SESSION['admin_token']` - Session token
- `$_SESSION['admin_login_log_id']` - Login log ID for tracking session

### 4. User Interface

#### Accessing the Profile
1. Click on the admin profile dropdown in the header (top-right corner)
2. Click "View Profile"
3. The profile page displays with all information and tabs

#### Profile Dropdown Menu
The header now displays:
- Admin's name (from session)
- Admin's email
- Profile avatar (auto-generated from name)
- Quick access to:
  - View Profile
  - Settings
  - Logout

### 5. Security Features

- All activity logs include IP addresses for security audit
- Failed login attempts are tracked
- Session duration monitoring
- Automatic activity logging on critical actions
- Cannot approve/reject own admin account
- Session validation on all admin pages

### 6. How Activity Logging Helps Admins

**1. Account Security**
- Monitor login attempts from unusual locations
- Track failed login attempts
- Identify unauthorized access patterns

**2. Accountability**
- Full audit trail of all actions
- Who did what and when
- Evidence for compliance and auditing

**3. Performance Monitoring**
- Session duration tracking
- Most frequent activities
- Usage patterns

**4. Troubleshooting**
- Review actions leading to issues
- Identify when changes were made
- Track notification sending history

### 7. Database Setup

Run the SQL script to create the necessary tables:

```bash
# Located at: ADMIN/api/create_admin_logs_tables.sql
```

Or the tables will be created automatically on first use.

### 8. Future Enhancements

Potential improvements:
- Export activity logs to CSV/PDF
- Advanced filtering and search
- Real-time activity monitoring dashboard
- Email alerts for suspicious activities
- Geolocation mapping of login attempts
- Two-factor authentication integration
- Password change history
- Admin action approvals for critical operations

## File Structure

```
EMERGENCY-COM/ADMIN/
├── api/
│   ├── profile.php                 # Profile API endpoint
│   ├── activity_logger.php         # Core activity logging functions
│   ├── log_activity_helper.php     # Simplified logging wrapper
│   ├── admin-approvals.php         # Admin approval (with logging)
│   └── create_admin_logs_tables.sql # Database schema
├── sidebar/
│   ├── profile.php                 # Admin profile page
│   └── includes/
│       └── admin-header.php        # Header with profile dropdown
└── DOCS/
    └── ADMIN_PROFILE_ACTIVITY_LOG.md # This documentation

```

## Usage Examples

### Example 1: Log a Notification Send
```php
<?php
session_start();
require_once '../api/log_activity_helper.php';

// After sending notification
$subscriberCount = 500;
logActivity('send_notification', "Sent emergency weather alert to {$subscriberCount} subscribers via SMS");
?>
```

### Example 2: Log User Management Action
```php
<?php
require_once '../api/activity_logger.php';

$adminId = $_SESSION['admin_user_id'];
$userId = 123;
$userName = "John Doe";

logAdminActivity($adminId, 'delete_user', "Deleted user account: {$userName} (ID: {$userId})");
?>
```

### Example 3: Query Activity Logs
```php
<?php
// Get last 10 activities for current admin
$stmt = $pdo->prepare("
    SELECT action, description, ip_address, created_at 
    FROM admin_activity_logs 
    WHERE admin_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['admin_user_id']]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
```

## Troubleshooting

### Activity logs not showing
1. Verify tables exist: `admin_activity_logs` and `admin_login_logs`
2. Check session is active: `$_SESSION['admin_user_id']`
3. Verify database connection in `db_connect.php`

### Profile page not loading
1. Check if logged in: `$_SESSION['admin_logged_in']`
2. Verify profile.php is in `sidebar/` directory
3. Check API endpoint: `api/profile.php` exists

### Session not persisting
1. Verify `session_start()` is called
2. Check for session cookie issues
3. Verify session timeout settings

## Best Practices

1. **Always log critical actions**: Approvals, deletions, mass notifications
2. **Use descriptive descriptions**: Include relevant details (user names, counts, etc.)
3. **Use consistent action types**: Follow the predefined action type list
4. **Don't log sensitive data**: Passwords, tokens, etc.
5. **Regular cleanup**: Archive old logs periodically (optional)

## Support

For questions or issues related to the admin profile and activity logging system:
1. Check this documentation
2. Review the code comments in the relevant files
3. Check error logs for detailed error messages
4. Verify database schema matches the SQL file

---

**Last Updated:** December 26, 2025  
**Version:** 1.0  
**Author:** Emergency Communication System Development Team


