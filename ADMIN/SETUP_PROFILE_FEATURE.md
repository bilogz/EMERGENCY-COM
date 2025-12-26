# Admin Profile & Activity Logging - Setup Guide

## Quick Setup Instructions

Follow these steps to enable the admin profile and activity logging features:

### Step 1: Create Database Tables

Run the SQL script to create the required tables:

```sql
-- Run this in phpMyAdmin or MySQL command line

-- Admin Activity Logs Table
CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action performed (e.g., login, logout, send_notification, etc.)',
    description TEXT DEFAULT NULL COMMENT 'Detailed description of the action',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address of the admin',
    user_agent TEXT DEFAULT NULL COMMENT 'Browser/user agent information',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Login Logs Table
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
    INDEX idx_login_at (login_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Alternative:** The SQL file is located at: `ADMIN/api/create_admin_logs_tables.sql`

### Step 2: Verify Files Are in Place

Make sure these files exist:

#### API Files
- ✅ `ADMIN/api/profile.php` - Profile data API
- ✅ `ADMIN/api/activity_logger.php` - Activity logging functions
- ✅ `ADMIN/api/log_activity_helper.php` - Simplified logging helper
- ✅ `ADMIN/api/admin-approvals.php` - Updated with activity logging

#### Page Files
- ✅ `ADMIN/sidebar/profile.php` - Admin profile page
- ✅ `ADMIN/sidebar/includes/admin-header.php` - Updated header with profile link

#### Documentation
- ✅ `DOCS/ADMIN_PROFILE_ACTIVITY_LOG.md` - Complete documentation

### Step 3: Test the Setup

1. **Login as Admin**
   - Go to: `http://localhost/EMERGENCY-COM/ADMIN/login.php`
   - Login with your admin credentials

2. **Check Header Display**
   - After login, look at the top-right corner
   - You should see your name displayed (not just "Admin User")
   - Click on your profile dropdown

3. **Access Profile Page**
   - Click "View Profile" from the dropdown
   - You should see:
     - Your profile information
     - Login statistics
     - Activity log tab
     - Login history tab

4. **Verify Activity Logging**
   - Perform some actions (e.g., approve an admin, navigate pages)
   - Go back to your profile page
   - Check the "Activity Log" tab
   - You should see your recent actions listed

### Step 4: Verify Database Data

Run this query to check if logs are being created:

```sql
-- Check activity logs
SELECT * FROM admin_activity_logs ORDER BY created_at DESC LIMIT 10;

-- Check login logs
SELECT * FROM admin_login_logs ORDER BY login_at DESC LIMIT 10;
```

## What You Should See

### 1. Profile Dropdown in Header
```
┌─────────────────────────────┐
│  👤 Your Name              │
│     your.email@example.com  │
├─────────────────────────────┤
│  👤 View Profile            │
│  ⚙️  Settings               │
├─────────────────────────────┤
│  🚪 Logout                  │
└─────────────────────────────┘
```

### 2. Profile Page Sections

**Left Panel:**
- Profile avatar with your initials
- Your full name
- Email address
- Administrator badge
- Account status
- Account created date
- Last login time

**Right Panel:**
- Statistics cards showing:
  - Total logins
  - Successful logins
  - Average session duration
- Top 5 activities you've performed

**Activity Tabs:**
- Activity Log: All your actions with timestamps
- Login History: Complete login/logout records

## Common Issues & Solutions

### Issue: "Admin User" still showing instead of real name
**Solution:**
1. Check if `$_SESSION['admin_username']` is set during login
2. Verify `login-web.php` is setting the session correctly
3. Clear browser cookies and login again

### Issue: Profile page shows "Loading..." forever
**Solution:**
1. Open browser console (F12) and check for JavaScript errors
2. Verify API endpoint: `api/profile.php` is accessible
3. Check database connection in `api/db_connect.php`
4. Verify tables exist in database

### Issue: Activity logs are empty
**Solution:**
1. Verify tables were created successfully
2. Check if `activity_logger.php` is being included
3. Perform some actions (login, logout, approve admin)
4. Refresh the profile page

### Issue: Database foreign key error
**Solution:**
The foreign key references `users(id)`. Make sure:
1. The `users` table exists
2. The `id` column in `users` is a primary key
3. If issues persist, remove `FOREIGN KEY` constraints from the SQL

## Testing Checklist

- [ ] Database tables created successfully
- [ ] Can login as admin
- [ ] Admin name shows in header (not "Admin User")
- [ ] Can click profile dropdown
- [ ] "View Profile" link works
- [ ] Profile page loads with information
- [ ] Statistics show correct numbers
- [ ] Activity log tab shows activities
- [ ] Login history tab shows logins
- [ ] Pagination works (if enough records)
- [ ] Filter works on activity log
- [ ] Can logout successfully
- [ ] Logout is logged in activity log

## Performance Considerations

For optimal performance with large amounts of log data:

1. **Regular Cleanup** (Optional)
```sql
-- Archive logs older than 6 months
DELETE FROM admin_activity_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

DELETE FROM admin_login_logs 
WHERE login_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

2. **Indexes**
The tables are already indexed on frequently queried columns (admin_id, created_at, action)

## Next Steps

After setup is complete:

1. **Train Other Admins**
   - Show them how to access their profile
   - Explain the activity log purpose
   - Encourage security awareness

2. **Monitor Activity**
   - Regularly review activity logs
   - Look for unusual patterns
   - Use for accountability and auditing

3. **Integrate Logging**
   - Add logging to other admin actions
   - Use `logActivity()` helper function
   - Follow the action type conventions

## Support

If you encounter issues:
1. Check error logs: `php_error.log`
2. Review browser console for JavaScript errors
3. Verify database connection
4. Check file permissions

---

**Setup Time:** ~5-10 minutes  
**Difficulty:** Easy  
**Database Changes:** 2 new tables  
**Breaking Changes:** None

