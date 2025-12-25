# User Database Schema Documentation

## Overview
This document describes the database schema for the Emergency Communication System user management module.

## Installation

### Step 1: Run the Main Schema
First, run the main database schema from `ADMIN/api/database_schema.sql` to create core system tables.

### Step 2: Run the User Schema
Then, run `USERS/database_schema.sql` to create all user-related tables.

```sql
-- In MySQL/MariaDB
SOURCE ADMIN/api/database_schema.sql;
SOURCE USERS/database_schema.sql;
```

Or import via phpMyAdmin or your preferred database tool.

## Database Tables

### 1. `users` - Main User Table
Stores all user/citizen information.

**Key Fields:**
- `id` - Primary key
- `name` - Full name
- `email` - Email address (optional)
- `phone` - Mobile phone number (required, unique)
- `password` - Hashed password (optional)
- `barangay`, `house_number`, `address` - Address information
- `status` - Account status (active, inactive, suspended, banned)
- `phone_verified` - Phone verification status
- `user_type` - Type of user (citizen, admin, guest)

**Indexes:**
- Unique on `phone`
- Unique on `email`
- Indexes on `name`, `status`, `user_type`

### 2. `otp_verifications` - OTP Management
Stores OTP codes for email verification (phone retained for legacy/backwards compatibility).

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table (can be NULL for new registrations)
- `phone` - Phone number to verify
- `otp_code` - 6-digit verification code
- `purpose` - Purpose (login, registration, password_reset)
- `status` - Status (pending, verified, expired, used)
- `expires_at` - Expiration timestamp
- `attempts` - Number of verification attempts

**Security Features:**
- Automatic expiration (10 minutes default)
- Maximum attempt limits
- IP address tracking

### 3. `user_sessions` - Session Management
Tracks user sessions for security.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `session_token` - Unique session token
- `ip_address` - User's IP address
- `user_agent` - Browser/device information
- `expires_at` - Session expiration
- `status` - Session status (active, expired, revoked)

### 4. `user_preferences` - User Settings
Stores user preferences and notification settings.

**Key Fields:**
- `user_id` - Reference to users table
- `sms_notifications`, `email_notifications`, `push_notifications` - Notification toggles
- `alert_categories` - Preferred alert categories
- `preferred_language` - Language preference
- `theme` - UI theme (light, dark, auto)

### 5. `user_activity_logs` - Activity Tracking
Audit log for user activities.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users (NULL for guests)
- `activity_type` - Type of activity (login, logout, profile_update, etc.)
- `ip_address` - IP address
- `status` - Activity status (success, failed, blocked)
- `metadata` - Additional JSON data

### 6. `emergency_contacts` - Emergency Contacts
Stores emergency contact information.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `contact_name` - Contact's name
- `contact_phone` - Contact's phone
- `relationship` - Relationship to user
- `is_primary` - Primary contact flag

### 7. `user_locations` - Location History
Tracks user locations for emergency services.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `latitude`, `longitude` - GPS coordinates
- `address` - Resolved address
- `is_current` - Current location flag

### 8. `password_reset_tokens` - Password Reset
Manages password reset tokens.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `token` - Reset token (unique)
- `expires_at` - Expiration time
- `status` - Status (pending, used, expired)

### 9. `user_devices` - Device Management
Tracks user devices for push notifications.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `device_id` - Unique device identifier
- `device_type` - Device type (ios, android, web)
- `push_token`, `fcm_token`, `apns_token` - Push notification tokens

### 10. `user_subscriptions` - Alert Subscriptions
Links users to alert categories.

**Key Fields:**
- `id` - Primary key
- `user_id` - Reference to users table
- `category_id` - Reference to alert_categories table
- `channels` - Notification channels (sms, email, push)
- `is_active` - Subscription status

## Relationships

```
users (1) ──< (many) otp_verifications
users (1) ──< (many) user_sessions
users (1) ──< (1) user_preferences
users (1) ──< (many) user_activity_logs
users (1) ──< (many) emergency_contacts
users (1) ──< (many) user_locations
users (1) ──< (many) password_reset_tokens
users (1) ──< (many) user_devices
users (1) ──< (many) user_subscriptions
alert_categories (1) ──< (many) user_subscriptions
```

## Maintenance

### Cleanup Queries
Run these periodically to clean up expired data:

```sql
-- Clean expired OTPs (daily)
DELETE FROM otp_verifications 
WHERE expires_at < NOW() AND status = 'pending';

-- Clean expired sessions (daily)
DELETE FROM user_sessions 
WHERE expires_at < NOW() AND status = 'active';

-- Clean expired password reset tokens (daily)
DELETE FROM password_reset_tokens 
WHERE expires_at < NOW() AND status = 'pending';

-- Archive old activity logs (monthly - keep last 6 months)
DELETE FROM user_activity_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

## Security Considerations

1. **Email Verification**: All users must verify their email address via OTP
2. **Session Management**: Sessions expire automatically and can be revoked
3. **Activity Logging**: All user activities are logged for audit
4. **Password Security**: Passwords are hashed using PHP's `password_hash()`
5. **OTP Security**: OTPs expire after 10 minutes and have attempt limits
6. **IP Tracking**: IP addresses are logged for security monitoring
---

## Email / SMTP Setup 🔧
To enable email OTP delivery in production, configure an SMTP server and PHPMailer:

1. Copy `USERS/config/mail_config.php.example` to `USERS/config/mail_config.php` and set your SMTP credentials (host, port, username, password, secure).
2. Install dependencies using Composer in the project root:

   composer install

   This will install `phpmailer/phpmailer` and create `vendor/autoload.php`.

3. Run the DB migration to add the `email` column if needed:

   - Execute `USERS/migrations/20251221_add_email_to_otp_verifications.sql` on your MySQL server (via phpMyAdmin or CLI).

4. Disable debug/`debug_otp` output in `USERS/api/send-otp.php` for production.

If you don't configure SMTP, the system will fall back to PHP's `mail()` (if available) or will return a `debug_otp` to the JSON response for local testing only. Replace debug behavior before deploying to production.
## Default Admin Account

A default admin account is created:
- **Email**: admin@emergency.com
- **Phone**: +639000000000
- **Password**: admin123 (CHANGE THIS IMMEDIATELY!)

**Important**: Change the default admin password immediately after installation!

## Notes

- All tables use `utf8mb4` character set for full Unicode support
- All timestamps use `DATETIME` type
- Foreign keys use `ON DELETE CASCADE` or `ON DELETE SET NULL` appropriately
- Indexes are optimized for common query patterns








