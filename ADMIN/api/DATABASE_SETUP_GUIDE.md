# Admin User Database Setup Guide

## Overview
This guide will help you set up the `admin_user` table, which separates admin accounts from regular user accounts.

## Setup Options

You have **two options** to set up the database:

### Option 1: PHP Script (Recommended)
**Easiest method - Run in your browser**

1. Open your browser
2. Navigate to: `http://localhost/EMERGENCY-COM/ADMIN/api/setup_admin_user_database.php`
3. The script will automatically:
   - Create the `admin_user` table
   - Migrate existing admin accounts from `users` table
   - Set the first admin as `super_admin`
   - Show you a summary of all admin accounts

**Advantages:**
- ✅ Visual feedback with colored status messages
- ✅ Shows all admin accounts in a table
- ✅ Displays statistics
- ✅ Handles errors gracefully

### Option 2: SQL File (phpMyAdmin/MySQL)
**For users comfortable with SQL**

1. Open phpMyAdmin (or MySQL command line)
2. Select your database (`emer_comm_test` or your database name)
3. Go to the "SQL" tab
4. Copy and paste the contents of `setup_admin_user_complete.sql`
5. Click "Go" (or press Enter)

**Advantages:**
- ✅ Direct SQL execution
- ✅ Can see exact SQL statements
- ✅ Good for debugging

## What Gets Created

### admin_user Table Structure
- `id` - Primary key (used in sessions)
- `user_id` - Reference to users table
- `name` - Full name
- `username` - Unique username
- `email` - Unique email address
- `password` - Hashed password
- `role` - 'super_admin', 'admin', or 'staff'
- `status` - 'active', 'inactive', 'suspended', 'pending_approval'
- `phone` - Phone number (optional)
- `created_by` - ID of admin who created this account
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `last_login` - Last login timestamp

## What Gets Migrated

All existing admin accounts from the `users` table will be:
- Copied to `admin_user` table
- Linked via `user_id` foreign key
- First admin automatically set as `super_admin`
- Status preserved (active/inactive/etc.)

## Verification

After running the setup, verify:

1. **Table exists:**
   ```sql
   SHOW TABLES LIKE 'admin_user';
   ```

2. **Admin accounts migrated:**
   ```sql
   SELECT COUNT(*) FROM admin_user;
   ```

3. **Super admin exists:**
   ```sql
   SELECT id, name, email, role, status FROM admin_user WHERE role = 'super_admin';
   ```

4. **All admins:**
   ```sql
   SELECT id, name, email, role, status FROM admin_user ORDER BY created_at ASC;
   ```

## Troubleshooting

### Error: "Table already exists"
- **Solution:** This is normal if you've run the script before. The script will continue and migrate any new admins.

### Error: "Foreign key constraint fails"
- **Solution:** Make sure the `users` table exists first. Run the main database schema if needed.

### Error: "Duplicate entry for email"
- **Solution:** Some admins may already be migrated. This is safe to ignore.

### No admins migrated
- **Solution:** Check if you have admin accounts in the `users` table:
  ```sql
  SELECT * FROM users WHERE user_type = 'admin';
  ```

## After Setup

Once the database is set up:

1. ✅ **Test Login:** Try logging in with an admin account
2. ✅ **Verify Session:** Check that `$_SESSION['admin_user_id']` contains `admin_user.id`
3. ✅ **Create Admin:** Super admin can now create new admin accounts
4. ✅ **Check Profile:** Admin profile should show data from `admin_user` table

## Next Steps

1. Run the setup script
2. Test admin login
3. Create new admin accounts (super admin only)
4. Review admin roles and permissions

## Support

If you encounter issues:
1. Check database connection in `db_connect.php`
2. Verify `users` table exists
3. Check PHP error logs
4. Review SQL error messages

---

**Ready to set up?** Choose Option 1 (PHP script) for the easiest experience!





