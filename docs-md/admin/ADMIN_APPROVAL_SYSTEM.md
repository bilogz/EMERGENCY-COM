# Admin Approval System

## Overview
The admin approval system ensures that new admin accounts require approval from an existing administrator before they can log in. This adds an extra layer of security and prevents unauthorized access to the admin panel.

## How It Works

### 1. Admin Account Creation
- When a new admin account is created via `create-admin.php`, the account status is set to `pending_approval` (unless it's the first admin account, which is auto-approved).
- The account creator receives a confirmation message indicating that the account is pending approval.

### 2. First Admin Exception
- If no active admins exist in the system (initial setup), the first admin account is automatically set to `active` status.
- This allows the system to be set up without requiring manual database intervention.

### 3. Login Restriction
- When a pending admin tries to log in, they receive a clear message:
  > "Your account is pending approval from an administrator. You will be able to log in once your account has been approved."

### 4. Approval Process
- Existing administrators can access the **Admin Approvals** page from the dashboard sidebar.
- The page displays:
  - Statistics: Number of pending approvals and active admins
  - List of all pending admin accounts with their details
  - Action buttons to approve or reject each account

### 5. Approval Actions
- **Approve**: Sets the account status to `active`, allowing the admin to log in immediately.
- **Reject**: Sets the account status to `inactive`, preventing login access.
- All approval/rejection actions are logged in the audit trail for security purposes.

## Files Modified/Created

### Modified Files:
1. **`create-admin.php`**
   - Changed default status from `active` to `pending_approval`
   - Added logic to auto-approve the first admin account
   - Updated success messages to reflect approval status

2. **`api/login-web.php`**
   - Added check for `pending_approval` status
   - Returns appropriate error message for pending accounts

3. **`sidebar/includes/sidebar.php`**
   - Added "Admin Approvals" menu item to the sidebar

4. **`api/database_schema.sql`**
   - Updated status field comment to include `pending_approval`

### New Files:
1. **`api/admin-approvals.php`**
   - API endpoint for managing admin approvals
   - Endpoints:
     - `GET ?action=list` - List all pending admin accounts
     - `POST ?action=approve` - Approve or reject an admin account
     - `GET ?action=stats` - Get approval statistics

2. **`sidebar/admin-approvals.php`**
   - Dashboard page for managing admin approvals
   - Features:
     - Real-time statistics display
     - Table of pending admin accounts
     - Approve/Reject buttons with confirmation dialogs
     - Auto-refresh functionality

## Database Changes

### Status Values
The `users.status` field now supports:
- `active` - Account is active and can log in
- `inactive` - Account is inactive (rejected or disabled)
- `suspended` - Account is temporarily suspended
- `pending_approval` - Account is waiting for admin approval (NEW)

### Audit Logging
All approval/rejection actions are logged in the `admin_audit_logs` table (created automatically if it doesn't exist) with:
- Admin ID who performed the action
- Action type (approve_admin, reject_admin)
- Description
- IP address
- Timestamp

## Security Features

1. **Authorization Check**: Only logged-in active admins can approve/reject accounts
2. **Self-Approval Prevention**: Admins cannot approve their own accounts
3. **Audit Trail**: All actions are logged for security monitoring
4. **Session Validation**: API endpoints verify admin session before processing requests
5. **Input Validation**: All user inputs are validated and sanitized

## Usage Instructions

### For Administrators:

1. **Access Admin Approvals**:
   - Log in to the admin dashboard
   - Click on "Admin Approvals" in the sidebar

2. **Review Pending Accounts**:
   - View the list of pending admin accounts
   - Check the statistics to see how many approvals are pending

3. **Approve an Account**:
   - Click the "Approve" button next to the admin account
   - Confirm the action in the dialog
   - The account will be immediately activated

4. **Reject an Account**:
   - Click the "Reject" button next to the admin account
   - Confirm the action in the dialog
   - The account will be set to inactive status

### For New Admins:

1. **Account Creation**:
   - An existing admin creates your account via `create-admin.php`
   - You receive a confirmation that your account is pending approval

2. **Waiting for Approval**:
   - You cannot log in until an admin approves your account
   - Attempting to log in will show a pending approval message

3. **After Approval**:
   - Once approved, you can log in normally
   - You'll have full admin access to the dashboard

## Best Practices

1. **Regular Review**: Check the Admin Approvals page regularly to process pending requests
2. **Verify Requests**: Before approving, verify that the account request is legitimate
3. **Documentation**: Keep records of who approved which accounts (audit log handles this)
4. **Communication**: Consider notifying the new admin via email once their account is approved

## Troubleshooting

### Issue: First admin cannot log in
**Solution**: The first admin should be auto-approved. If not, manually update the database:
```sql
UPDATE users SET status = 'active' WHERE user_type = 'admin' AND id = [user_id];
```

### Issue: Approved admin still cannot log in
**Solution**: 
1. Verify the account status in the database
2. Check if the account was actually approved (check audit logs)
3. Clear browser cache and try again

### Issue: Admin Approvals page shows no data
**Solution**:
1. Check browser console for JavaScript errors
2. Verify API endpoint is accessible: `api/admin-approvals.php?action=list`
3. Check database connection and user permissions

## Future Enhancements

Potential improvements for the approval system:
- Email notifications when accounts are approved/rejected
- Bulk approval/rejection actions
- Approval workflow with multiple approvers
- Time-based auto-rejection of old pending requests
- Approval history and comments












