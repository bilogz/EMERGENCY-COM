# Admin Login System Update - Separation of Users and Admins

## Overview
The login system has been updated to separate admin authentication from regular user authentication. Admin logins now exclusively fetch from the `admin_user` table, ensuring complete separation between regular users and administrators.

## Changes Made

### 1. Login System (`api/login-web.php`)
- **Before**: Queried `users` table with `user_type = 'admin'`
- **After**: Queries `admin_user` table first, falls back to `users` table for backward compatibility
- **Session Storage**: Stores `admin_user.id` in `$_SESSION['admin_user_id']`
- **Additional Session Data**: 
  - `admin_user_table_id`: Stores admin_user.id separately
  - `admin_role`: Stores admin role (super_admin, admin, staff)
- **Last Login Update**: Updates `last_login` timestamp in `admin_user` table on successful login

### 2. OTP System (`api/send-admin-otp.php`)
- **Before**: Checked `users` table for admin accounts
- **After**: Checks `admin_user` table first, falls back to `users` table
- **Login Purpose**: Validates admin exists in `admin_user` table before sending OTP

### 3. Profile System (`api/get-admin-profile.php`)
- **Before**: Fetched from `users` table
- **After**: Fetches from `admin_user` table with additional fields:
  - `user_id`: Reference to users table
  - `username`: Admin username
  - `role`: Admin role (super_admin, admin, staff)
  - `last_login`: Last login timestamp
- **Backward Compatibility**: Falls back to `users` table if `admin_user` doesn't exist

### 4. Security Helpers (`api/security-helpers.php`)
- **Before**: Checked `users` table for admin authorization
- **After**: Checks `admin_user` table using `admin_user.id` (stored in session)
- **Super Admin Check**: Verifies `role = 'super_admin'` in `admin_user` table

### 5. Admin Approvals (`api/admin-approvals.php`)
- **Before**: Managed approvals in `users` table
- **After**: Manages approvals in `admin_user` table
- **Synchronization**: Updates both `admin_user` and `users` tables when approving/rejecting
- **Statistics**: Counts from `admin_user` table

### 6. Profile API (`api/profile.php`)
- **Before**: Fetched profile from `users` table
- **After**: Fetches from `admin_user` table with role information
- **Activity Logs**: Uses `admin_user.id` for activity logging

## Database Structure

### admin_user Table
```sql
- id: Primary key (used in sessions)
- user_id: Reference to users table
- name: Full name
- username: Unique username
- email: Unique email
- password: Hashed password
- role: 'super_admin', 'admin', or 'staff'
- status: 'active', 'inactive', 'suspended', 'pending_approval'
- phone: Phone number
- created_by: ID of admin who created this account
- created_at: Creation timestamp
- updated_at: Last update timestamp
- last_login: Last login timestamp
```

## Session Variables

After login, the following session variables are set:
- `admin_logged_in`: Boolean (true)
- `admin_user_id`: admin_user.id (primary identifier)
- `admin_user_table_id`: admin_user.id (duplicate for clarity)
- `admin_username`: Admin's display name
- `admin_email`: Admin's email
- `admin_role`: Admin's role (super_admin, admin, staff)
- `admin_token`: Security token

## Backward Compatibility

All updated files include backward compatibility:
- Checks if `admin_user` table exists
- Falls back to `users` table if `admin_user` doesn't exist
- Ensures smooth migration without breaking existing installations

## Migration Steps

1. **Run Migration Script**: Execute `api/migrate_to_admin_user.php`
   - Creates `admin_user` table
   - Migrates existing admin accounts from `users` table
   - Sets first admin as `super_admin`

2. **Test Login**: 
   - Log in with an existing admin account
   - Verify session variables are set correctly
   - Check that `admin_user` table is being used

3. **Verify Functionality**:
   - Test admin account creation (super admin only)
   - Test admin approvals
   - Test profile retrieval
   - Test activity logging

## Benefits

1. **Separation of Concerns**: Admins and users are completely separated
2. **Enhanced Security**: Admin-specific security measures
3. **Role-Based Access**: Clear role hierarchy (super_admin > admin > staff)
4. **Better Audit Trail**: Admin actions tracked separately
5. **Scalability**: Easier to add admin-specific features
6. **Data Integrity**: Foreign key relationships maintained

## Important Notes

- **Session ID**: `$_SESSION['admin_user_id']` now stores `admin_user.id`, not `users.id`
- **Activity Logs**: All activity logs use `admin_user.id` as `admin_id`
- **Login Logs**: Login logs reference `admin_user.id`
- **Foreign Keys**: `admin_user.user_id` links to `users.id` for user data

## Troubleshooting

### Issue: "Admin not found" after login
**Solution**: Ensure `admin_user` table exists and admin account was migrated

### Issue: Cannot create admin accounts
**Solution**: Verify logged-in admin has `role = 'super_admin'` in `admin_user` table

### Issue: Session not working
**Solution**: Check that `admin_user_id` in session matches `admin_user.id` in database

## Files Modified

1. `api/login-web.php` - Login authentication
2. `api/send-admin-otp.php` - OTP validation
3. `api/get-admin-profile.php` - Profile retrieval
4. `api/security-helpers.php` - Authorization checks
5. `api/admin-approvals.php` - Admin approval management
6. `api/profile.php` - Profile API

## Testing Checklist

- [ ] Admin login works with admin_user table
- [ ] OTP sending works for admin_user accounts
- [ ] Profile retrieval shows admin_user data
- [ ] Super admin can create admin accounts
- [ ] Admin approvals work with admin_user table
- [ ] Activity logs use admin_user.id
- [ ] Backward compatibility works (users table fallback)
- [ ] Session variables are set correctly
- [ ] Last login timestamp updates

