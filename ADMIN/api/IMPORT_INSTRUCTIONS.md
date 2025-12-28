# Database Import Instructions

## Quick Import Guide

This file contains complete SQL queries to set up your entire database.

## Method 1: Import via phpMyAdmin (Easiest)

1. **Open phpMyAdmin**
   - Go to: `https://alertaraqc.com/phpmyadmin/`
   - Login with: `root` / `YsqnXk6q#145`

2. **Select Database**
   - Click on `emer_comm_test` in the left sidebar
   - If it doesn't exist, create it first (or the SQL will create it)

3. **Import SQL File**
   - Click on the **"Import"** tab at the top
   - Click **"Choose File"** button
   - Select: `complete_database_setup.sql`
   - Click **"Go"** button at the bottom

4. **Verify**
   - You should see "Import has been successfully finished"
   - Check the left sidebar - you should see all tables listed

## Method 2: Run via Browser Script

1. **Run the setup script:**
   ```
   http://your-domain/EMERGENCY-COM/ADMIN/api/quick_setup.php
   ```

2. **Or run the direct users table creator:**
   ```
   http://your-domain/EMERGENCY-COM/ADMIN/api/create_users_table.php
   ```

## Method 3: Command Line (if you have SSH access)

```bash
mysql -u root -p'YsqnXk6q#145' < complete_database_setup.sql
```

Or:

```bash
mysql -u root -p'YsqnXk6q#145' emer_comm_test < complete_database_setup.sql
```

## What This SQL File Creates

### Tables Created:
1. ✅ **users** - User accounts (admin, citizen, guest)
2. ✅ **notification_logs** - All notification history
3. ✅ **alert_categories** - Alert category definitions
4. ✅ **alerts** - Emergency alerts
5. ✅ **conversations** - Two-way communication conversations
6. ✅ **messages** - Messages within conversations
7. ✅ **integration_settings** - API keys for PAGASA, PHIVOLCS
8. ✅ **warning_settings** - Automated warning configuration
9. ✅ **automated_warnings** - Received automated warnings
10. ✅ **alert_translations** - Multilingual alert translations
11. ✅ **subscriptions** - Citizen subscription preferences
12. ✅ **otp_verifications** - OTP verification for admin login/account creation

### Default Data Inserted:
- ✅ 5 Alert Categories (Weather, Earthquake, Bomb Threat, Fire, General)
- ✅ Integration Settings (PAGASA, PHIVOLCS)
- ✅ Warning Settings (default sync interval)

## Verification

After importing, verify tables exist:

```sql
SHOW TABLES;
```

Should show all 12 tables.

Check users table structure:

```sql
DESCRIBE users;
```

Should show: id, name, username, email, phone, password, status, user_type, created_at, updated_at

## Troubleshooting

### Error: "Table already exists"
- This is OK - the SQL uses `CREATE TABLE IF NOT EXISTS`
- Tables won't be overwritten

### Error: "Database doesn't exist"
- The SQL will create `emer_comm_test` automatically
- Or create it manually in phpMyAdmin first

### Error: "Access denied"
- Check your MySQL credentials
- Verify user `root` has CREATE privileges

### After Import - Still Getting Errors?
1. Verify tables exist: `SHOW TABLES;`
2. Check users table: `DESCRIBE users;`
3. Try creating an admin account again
4. Check PHP error logs for specific errors

## Next Steps

After successful import:

1. ✅ Test create-admin.php
2. ✅ Configure PAGASA API key in Automated Warnings
3. ✅ Set up email/SMS gateway (if needed)
4. ✅ Test all modules

## File Location

The SQL file is located at:
```
EMERGENCY-COM/ADMIN/api/complete_database_setup.sql
```

You can download it and import it via phpMyAdmin, or run it via command line.

