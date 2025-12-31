# Deployment Guide - SSH Server

This guide will help you deploy the Emergency Communication System to your SSH server.

## Prerequisites

- SSH access to your server (alertaraqc.com)
- MySQL/MariaDB running on the server
- PHP 7.4+ installed
- Web server (Apache/Nginx) configured

## Step 1: Clean Up Debug Files

Before deploying, clean up all debug and test files:

1. **Via Browser (Recommended):**
   ```
   http://localhost/EMERGENCY-COM/ADMIN/api/cleanup-debug-files.php
   ```

2. **Or manually delete these files:**
   - All `debug-*.php` files
   - All `test-*.php` files
   - All `check-*.php` files
   - `setup-admin-user-table.php`
   - `create-otp-table.php`
   - `generate-password-hash.php`
   - `reset-login-attempts.php`
   - `update-password-joecel519.sql`

## Step 2: Upload Files to SSH Server

### Option A: Using SCP (Command Line)

```bash
# Upload entire EMERGENCY-COM directory
scp -r EMERGENCY-COM/ user@alertaraqc.com:/path/to/webroot/

# Or upload specific directories
scp -r EMERGENCY-COM/ADMIN/ user@alertaraqc.com:/path/to/webroot/EMERGENCY-COM/ADMIN/
scp -r EMERGENCY-COM/USERS/ user@alertaraqc.com:/path/to/webroot/EMERGENCY-COM/USERS/
```

### Option B: Using SFTP Client

1. Connect to your server via SFTP (FileZilla, WinSCP, etc.)
2. Navigate to your web root directory
3. Upload the `EMERGENCY-COM` folder

### Option C: Using Git (Recommended)

```bash
# On your local machine
cd EMERGENCY-COM
git add .
git commit -m "Production deployment - cleaned up debug files"
git push origin main

# On SSH server
cd /path/to/webroot/EMERGENCY-COM
git pull origin main
```

## Step 3: Set Up Database on SSH Server

1. **SSH into your server:**
   ```bash
   ssh user@alertaraqc.com
   ```

2. **Import the complete database schema:**
   ```bash
   mysql -u root -p emer_comm_test < /path/to/EMERGENCY-COM/complete_database_schema.sql
   ```

   Or via phpMyAdmin:
   - Go to: `http://alertaraqc.com/phpmyadmin`
   - Select database: `emer_comm_test`
   - Go to "Import" tab
   - Upload: `complete_database_schema.sql`
   - Click "Go"

3. **Verify tables were created:**
   ```sql
   SHOW TABLES;
   ```
   
   You should see all 27 tables.

## Step 4: Configure Database Connection

1. **Edit the config file on the server:**
   ```bash
   nano /path/to/EMERGENCY-COM/ADMIN/api/config.local.php
   ```

2. **Verify database settings:**
   - `DB_HOST`: Should be `localhost` (when PHP and MySQL are on same server)
   - `DB_PORT`: `3306` (or your MySQL port)
   - `DB_NAME`: `emer_comm_test`
   - `DB_USER`: `root` (or your MySQL user)
   - `DB_PASS`: Your MySQL password

3. **Verify OTP settings:**
   - `ADMIN_REQUIRE_OTP`: Should be `true` for production

## Step 5: Set File Permissions

```bash
# Set proper permissions
chmod 644 EMERGENCY-COM/ADMIN/api/config.local.php
chmod 644 EMERGENCY-COM/USERS/api/config.local.php
chmod 755 EMERGENCY-COM/ADMIN/api/
chmod 755 EMERGENCY-COM/USERS/api/
```

## Step 6: Create Admin Account

1. **Via Browser:**
   ```
   http://alertaraqc.com/EMERGENCY-COM/ADMIN/api/setup-admin-user-table.php
   ```
   
   (Note: This file should be deleted after use for security)

2. **Or via SQL:**
   ```sql
   INSERT INTO admin_user (name, username, email, password, role, status) 
   VALUES (
       'Super Administrator',
       'admin',
       'joecel519@gmail.com',
       '$2y$10$JtELZpmSLrNenCmv.TznheXqLk0/dysfYtsxEIQ9JIHfNRZu2mtMS',
       'super_admin',
       'active'
   );
   ```
   
   Password: `Admin#123`

## Step 7: Verify Deployment

1. **Test database connection:**
   ```
   http://alertaraqc.com/EMERGENCY-COM/ADMIN/api/test_database_connection.php
   ```
   (Delete this file after testing)

2. **Test login:**
   ```
   http://alertaraqc.com/EMERGENCY-COM/ADMIN/login.php
   ```
   - Email: `joecel519@gmail.com`
   - Password: `Admin#123`
   - Should require OTP verification

3. **Check OTP sending:**
   - Login should trigger OTP email
   - Check email for verification code

## Step 8: Security Checklist

- [ ] All debug files removed
- [ ] `config.local.php` has correct database credentials
- [ ] `config.local.php` is NOT in Git (check .gitignore)
- [ ] File permissions set correctly
- [ ] OTP is enabled (`ADMIN_REQUIRE_OTP => true`)
- [ ] Admin account created and tested
- [ ] Database schema imported successfully
- [ ] All 27 tables exist in database

## Step 9: Post-Deployment

1. **Delete setup scripts:**
   - `setup-admin-user-table.php`
   - `create-otp-table.php`
   - Any other one-time setup scripts

2. **Set up email configuration:**
   - Configure SMTP settings in `config.local.php`
   - Test email sending

3. **Configure reCAPTCHA:**
   - Update `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY` with production keys
   - Remove test keys

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running: `sudo systemctl status mysql`
- Check MySQL port: `netstat -tlnp | grep 3306`
- Verify credentials in `config.local.php`

### OTP Not Sending
- Check SMTP configuration
- Verify `otp_verifications` table exists
- Check email logs

### Login Issues
- Clear browser cache and cookies
- Check `admin_user` table exists
- Verify password hash matches

## Support

For issues, check:
- Error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- PHP error logs: Check `php.ini` for `error_log` location
- Database logs: MySQL error log

---

**Last Updated:** 2024
**Version:** 1.0

