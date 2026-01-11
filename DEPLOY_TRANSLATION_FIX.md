# Deployment Guide: Translation API Fix

## Files Modified
The following files were fixed to resolve the 500 Internal Server Error in the translation API:

1. **USERS/api/translate-alert-text.php** - Added error handling and global $pdo access
2. **ADMIN/api/ai-translation-service.php** - Fixed global $pdo access for secure-api-config.php
3. **ADMIN/api/secure-api-config.php** - Added $GLOBALS['pdo'] fallback support

---

## Quick Deployment Steps

### Option 1: Using Git (Recommended)

#### On Your Local Machine:

```bash
# Navigate to project directory
cd C:\xampp\htdocs\EMERGENCY-COM

# Check current status
git status

# Add the modified files
git add USERS/api/translate-alert-text.php
git add ADMIN/api/ai-translation-service.php
git add ADMIN/api/secure-api-config.php

# Commit with descriptive message
git commit -m "Fix: Resolve 500 errors in translation API - Add global pdo access and error handling"

# Push to repository
git push origin main
```

#### On Production Server (SSH):

```bash
# SSH into your server
ssh user@alertaraqc.com

# Navigate to project directory
cd /path/to/EMERGENCY-COM  # Replace with your actual path

# Pull latest changes
git pull origin main

# Verify files were updated
ls -la USERS/api/translate-alert-text.php
ls -la ADMIN/api/ai-translation-service.php
ls -la ADMIN/api/secure-api-config.php
```

---

### Option 2: Using FTP/SFTP (FileZilla, WinSCP, etc.)

#### Step 1: Prepare Files Locally
1. Open FileZilla or your FTP client
2. Connect to: `alertaraqc.com`
3. Navigate to your local files:
   - `C:\xampp\htdocs\EMERGENCY-COM\USERS\api\translate-alert-text.php`
   - `C:\xampp\htdocs\EMERGENCY-COM\ADMIN\api\ai-translation-service.php`
   - `C:\xampp\htdocs\EMERGENCY-COM\ADMIN\api\secure-api-config.php`

#### Step 2: Upload to Server
1. Navigate to server directory:
   - `/public_html/EMERGENCY-COM/USERS/api/` (or your web root)
   - `/public_html/EMERGENCY-COM/ADMIN/api/`
2. Upload the 3 files, **overwriting** existing files
3. Set file permissions to **644**:
   ```bash
   chmod 644 USERS/api/translate-alert-text.php
   chmod 644 ADMIN/api/ai-translation-service.php
   chmod 644 ADMIN/api/secure-api-config.php
   ```

---

### Option 3: Using SCP (Command Line)

```bash
# From your local machine (PowerShell or Command Prompt)
scp USERS/api/translate-alert-text.php user@alertaraqc.com:/path/to/EMERGENCY-COM/USERS/api/
scp ADMIN/api/ai-translation-service.php user@alertaraqc.com:/path/to/EMERGENCY-COM/ADMIN/api/
scp ADMIN/api/secure-api-config.php user@alertaraqc.com:/path/to/EMERGENCY-COM/ADMIN/api/
```

---

## Post-Deployment Verification

### 1. Test Translation API Directly

Open browser and test:
```
https://emergency-comm.alertaraqc.com/USERS/api/translate-alert-text.php
```

**Expected Response (for GET request):**
```json
{
  "success": false,
  "message": "Method not allowed"
}
```

This confirms the file is accessible and working.

### 1b. Test with Debug Mode (If Errors Persist)

Add `?debug=1` to see detailed error information:
```
https://emergency-comm.alertaraqc.com/USERS/api/translate-alert-text.php?debug=1
```

This will show detailed debug information including file paths and error traces.

### 2. Test from Alerts Page

1. Go to: `https://emergency-comm.alertaraqc.com/USERS/alerts.php`
2. Open browser Developer Console (F12)
3. Check Console tab for errors
4. **Expected:** No more 500 errors from `translate-alert-text.php`
5. Alerts should translate properly if AI Translation is enabled

### 3. Check Server Error Logs

```bash
# SSH into server
ssh user@alertaraqc.com

# Check PHP error logs
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/php_errors.log
# OR (cPanel)
tail -f ~/logs/error_log
```

Look for any new errors related to translation API.

### 4. Verify File Permissions

```bash
# On server
ls -la USERS/api/translate-alert-text.php
ls -la ADMIN/api/ai-translation-service.php
ls -la ADMIN/api/secure-api-config.php

# Should show: -rw-r--r-- (644)
```

---

## Rollback Plan (If Issues Occur)

If the fix causes problems, you can rollback:

### Option 1: Git Rollback
```bash
# On server
cd /path/to/EMERGENCY-COM
git log --oneline  # Find previous commit
git checkout <previous-commit-hash> -- USERS/api/translate-alert-text.php
git checkout <previous-commit-hash> -- ADMIN/api/ai-translation-service.php
git checkout <previous-commit-hash> -- ADMIN/api/secure-api-config.php
```

### Option 2: Restore from Backup
```bash
# If you have backups
cp backup/translate-alert-text.php USERS/api/
cp backup/ai-translation-service.php ADMIN/api/
cp backup/secure-api-config.php ADMIN/api/
```

---

## What Was Fixed

### Issue:
- Translation API was returning 500 Internal Server Error
- `secure-api-config.php` couldn't access `$pdo` variable in global scope
- Fatal errors weren't being caught properly

### Solution:
1. **Added global $pdo access** - Set `$GLOBALS['pdo']` before requiring files
2. **Added fallback in secure-api-config.php** - Checks `$GLOBALS['pdo']` if `global $pdo` is null
3. **Added fatal error handler** - Catches errors that occur before try-catch blocks
4. **Improved error logging** - Better error messages for debugging

---

## Configuration Check

After deployment, verify these settings in `ADMIN/api/config.local.php`:

```php
// AI Translation API Key (if using AI translation)
'AI_API_KEY_TRANSLATION' => 'your-key-here',

// Database connection (should already be configured)
'DB_HOST' => 'localhost',
'DB_NAME' => 'emer_comm_test',
// etc...
```

---

## Troubleshooting

### If 500 Errors Still Occur After Deployment

1. **Check the actual error response:**
   - Open browser console (F12)
   - Go to Network tab
   - Click on the failed `translate-alert-text.php` request
   - Check the Response tab to see the actual error message

2. **Test with debug mode:**
   ```
   https://emergency-comm.alertaraqc.com/USERS/api/translate-alert-text.php?debug=1
   ```
   This will show detailed error information.

3. **Check server error logs:**
   ```bash
   # SSH into server
   ssh user@alertaraqc.com
   
   # Check PHP error logs
   tail -f /var/log/apache2/error.log
   # OR (cPanel)
   tail -f ~/logs/error_log
   # OR (check PHP error log location)
   php -i | grep error_log
   ```

4. **Verify file paths exist on server:**
   ```bash
   # On server, check if files exist
   ls -la /path/to/EMERGENCY-COM/ADMIN/api/ai-translation-service.php
   ls -la /path/to/EMERGENCY-COM/ADMIN/api/secure-api-config.php
   ls -la /path/to/EMERGENCY-COM/ADMIN/api/activity_logger.php
   ```

5. **Verify database connection:**
   - Check `ADMIN/api/config.local.php` has correct database credentials
   - Test database connection from server

6. **Check PHP version:**
   ```bash
   php -v  # Should be PHP 7.4 or higher
   ```

## Support

If issues persist after deployment:

1. **Check browser console** (F12) for JavaScript errors
2. **Check server error logs** for PHP errors
3. **Verify database connection** is working
4. **Test AI Translation API** is enabled in admin settings:
   - Go to: General Settings → System Settings → AI Translation API
   - Ensure it's enabled if you want translations
5. **Check the Network tab** in browser console to see the actual error response from the API

---

## Summary

✅ **Files to Deploy:**
- `USERS/api/translate-alert-text.php`
- `ADMIN/api/ai-translation-service.php`
- `ADMIN/api/secure-api-config.php`

✅ **Quick Test:**
- Open alerts page
- Check browser console - should see no 500 errors
- Alerts should translate (if AI Translation enabled)

✅ **Estimated Deployment Time:** 5-10 minutes

---

**Last Updated:** 2026-01-11
**Status:** Ready for Production Deployment ✅
