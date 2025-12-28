# Admin Account Creation System

## Overview
This system provides a secure, role-based admin account creation feature. Only **super administrators** can create new admin accounts, preventing unauthorized access and manipulation.

## Features

### Security Features
- ✅ **Super Admin Only**: Only users with `super_admin` role can create admin accounts
- ✅ **CSRF Protection**: All forms use CSRF tokens to prevent cross-site request forgery
- ✅ **Password Strength Validation**: Enforces strong passwords (8+ chars, uppercase, lowercase, number, special char)
- ✅ **Rate Limiting**: Prevents brute force attacks with IP-based rate limiting
- ✅ **Input Validation**: All inputs are sanitized and validated
- ✅ **Audit Logging**: All account creation activities are logged

### User Experience Features
- ✅ **Modern UI**: Beautiful, responsive design with smooth animations
- ✅ **Real-time Validation**: Form fields validate as you type
- ✅ **Password Strength Indicator**: Visual feedback for password strength
- ✅ **Clear Error Messages**: User-friendly error messages
- ✅ **Loading States**: Visual feedback during form submission

## Database Structure

### admin_user Table
The system uses a dedicated `admin_user` table for admin accounts:

```sql
- id: Primary key
- user_id: Reference to users table
- name: Full name
- username: Unique username
- email: Unique email address
- password: Hashed password
- role: 'super_admin', 'admin', or 'staff'
- status: 'active', 'inactive', 'suspended', or 'pending_approval'
- phone: Phone number (optional)
- created_by: ID of admin who created this account
- created_at: Creation timestamp
- updated_at: Last update timestamp
- last_login: Last login timestamp
```

### Role Hierarchy
1. **super_admin**: Can create admin accounts, full system access
2. **admin**: Standard administrator privileges
3. **staff**: Limited administrative privileges

## Installation

### Step 1: Run Migration Script
Navigate to: `ADMIN/api/migrate_to_admin_user.php`

This will:
- Create the `admin_user` table
- Migrate existing admin accounts from `users` table
- Set the first admin as `super_admin`

### Step 2: Verify Installation
After running the migration:
1. Check that `admin_user` table exists
2. Verify existing admin accounts were migrated
3. Confirm first admin has `super_admin` role

## Usage

### Creating an Admin Account

1. **Login as Super Admin**
   - Only super administrators can access the create admin page
   - Regular admins will see an "Access Denied" message

2. **Access Create Admin Page**
   - Navigate to: `ADMIN/create-admin.php`
   - Or add a link in your admin dashboard

3. **Fill Out the Form**
   - Full Name: At least 2 characters
   - Username: 3+ characters, alphanumeric and underscores only
   - Email: Valid email address
   - Phone: Optional phone number
   - Password: Must meet strength requirements
   - Confirm Password: Must match password
   - Role: Select 'admin' or 'staff'

4. **Submit**
   - Form validates all fields
   - Account is created in `admin_user` table
   - Status is set to 'active' if first admin, otherwise 'pending_approval'

### Account Status

- **active**: Account can log in immediately
- **pending_approval**: Account needs super admin approval (for non-first admins)
- **inactive**: Account is disabled
- **suspended**: Account is temporarily suspended

## API Endpoint

### POST `/ADMIN/api/create-admin-account.php`

**Authentication**: Requires super admin session

**Request Body**:
```json
{
  "csrf_token": "token_from_session",
  "name": "John Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "phone": "+639123456789",
  "password": "SecurePass123!",
  "confirm_password": "SecurePass123!",
  "role": "admin"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Admin account created successfully!",
  "admin_id": 123,
  "status": "active"
}
```

## Security Considerations

### Authorization Check
The system checks:
1. User is logged in (`admin_logged_in` session)
2. User has valid admin session (`admin_user_id`)
3. User has `super_admin` role in `admin_user` table
4. User account is active

### Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character
- Cannot be common passwords

### Rate Limiting
- Maximum 5 attempts per hour per IP address
- Prevents brute force attacks
- Resets after time window expires

## Files Modified/Created

### New Files
1. `ADMIN/api/create_admin_user_table.sql` - Database schema
2. `ADMIN/api/create-admin-account.php` - API endpoint
3. `ADMIN/api/migrate_to_admin_user.php` - Migration script
4. `ADMIN/create-admin.php` - Updated UI page

### Modified Files
1. `ADMIN/api/security-helpers.php` - Updated authorization checks

## Troubleshooting

### "Access Denied" Error
- Ensure you're logged in as a super admin
- Check that your account has `role = 'super_admin'` in `admin_user` table
- Verify your session is valid

### Migration Issues
- Ensure `users` table exists
- Check database permissions
- Verify foreign key constraints are enabled

### Account Creation Fails
- Check database connection
- Verify all required fields are provided
- Check for duplicate email/username
- Review server error logs

## Best Practices

1. **First Admin Setup**: The first admin account is automatically set as `super_admin`
2. **Regular Audits**: Review admin accounts regularly
3. **Strong Passwords**: Enforce password policies
4. **Monitor Logs**: Check audit logs for suspicious activity
5. **Limit Super Admins**: Only create super admin accounts when necessary

## Support

For issues or questions:
1. Check error logs in `ADMIN/api/`
2. Review database for constraint violations
3. Verify session and authentication state
4. Check network connectivity for API calls

