# Testing Guide - Admin Login & Account Creation

## ✅ What Was Fixed

### 1. **Database Schema Compatibility**
- Fixed `create-admin.php` to match actual database schema
- Removed `username` column reference (not in schema)
- Uses `name`, `email`, `password`, `status`, `user_type` columns

### 2. **Debug Mode Removed**
- Removed debug mode from `create-admin.php` (production-ready)

### 3. **Database Connection**
- Improved connection handling with fallback port support
- Tries default port (3306) first, then specified port

### 4. **reCAPTCHA Integration**
- Fixed reCAPTCHA validation in both login and create-admin
- Proper error handling for missing reCAPTCHA

### 5. **Error Handling**
- Better error messages
- Proper exception handling
- Security-friendly error messages (no sensitive data exposed)

---

## 🧪 Testing Steps

### Test 1: Database Connection

1. **Check if database exists:**
   ```sql
   SHOW DATABASES LIKE 'emergency_comm_db';
   ```

2. **Check if users table exists:**
   ```sql
   USE emergency_comm_db;
   SHOW TABLES LIKE 'users';
   DESCRIBE users;
   ```

3. **Verify table structure:**
   - Should have: `id`, `name`, `email`, `password`, `status`, `user_type`, `created_at`, `updated_at`
   - Should NOT have: `username` column

---

### Test 2: Create Admin Account

1. **Navigate to:** `http://localhost/EMERGENCY-COM/ADMIN/create-admin.php`

2. **Fill in the form:**
   - Full Name: `Test Admin`
   - Username: (optional, can leave empty)
   - Email: `admin@test.com`
   - Password: `test123`
   - Confirm Password: `test123`
   - Role: `Admin`
   - Complete reCAPTCHA: Check "I am not a robot"

3. **Expected Result:**
   - ✅ Success message: "Admin staff account created successfully!"
   - ✅ Account saved to database
   - ✅ Can now login with this account

4. **Test Validation:**
   - Try with empty fields → Should show error
   - Try with mismatched passwords → Should show error
   - Try with existing email → Should show "email already exists"
   - Try without reCAPTCHA → Should show error

---

### Test 3: Login Functionality

1. **Navigate to:** `http://localhost/EMERGENCY-COM/ADMIN/login.php`

2. **Login with created account:**
   - Email: `admin@test.com`
   - Password: `test123`
   - Complete reCAPTCHA: Check "I am not a robot"
   - Click "Sign In"

3. **Expected Result:**
   - ✅ Success message
   - ✅ Redirect to `sidebar/dashboard.php`
   - ✅ Session created

4. **Test Error Cases:**
   - Wrong email → Should show "Invalid email or password"
   - Wrong password → Should show "Invalid email or password"
   - Without reCAPTCHA → Should show error
   - After 5 failed attempts → Account locked for 15 minutes

---

### Test 4: Database Verification

1. **Check created account:**
   ```sql
   SELECT id, name, email, status, user_type, created_at 
   FROM users 
   WHERE email = 'admin@test.com';
   ```

2. **Verify password is hashed:**
   ```sql
   SELECT password FROM users WHERE email = 'admin@test.com';
   ```
   - Should show hashed password (starts with `$2y$`)

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"

**Solution:**
1. Check if MySQL is running in XAMPP
2. Verify database name: `emergency_comm_db`
3. Check port in `api/db_connect.php`:
   - Default XAMPP: `3306`
   - If using custom port: Change `$port = 3000;` (or your port)

4. Test connection:
   ```php
   // Create test file: ADMIN/api/test-db.php
   <?php
   require_once 'db_connect.php';
   echo "Connected successfully!";
   ?>
   ```

---

### Issue: "Table 'users' doesn't exist"

**Solution:**
1. Run the database schema:
   ```sql
   SOURCE EMERGENCY-COM/ADMIN/api/database_schema.sql;
   ```

2. Or create manually:
   ```sql
   CREATE TABLE IF NOT EXISTS users (
       id INT AUTO_INCREMENT PRIMARY KEY,
       name VARCHAR(255) NOT NULL,
       email VARCHAR(255) DEFAULT NULL,
       phone VARCHAR(20) DEFAULT NULL,
       password VARCHAR(255) DEFAULT NULL,
       status VARCHAR(20) DEFAULT 'active',
       user_type VARCHAR(20) DEFAULT 'citizen',
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   );
   ```

---

### Issue: "reCAPTCHA not showing"

**Solution:**
1. Check browser console for errors
2. Verify Google reCAPTCHA script is loading:
   ```html
   <script src="https://www.google.com/recaptcha/api.js" async defer></script>
   ```
3. Check if Site Key is correct
4. Verify internet connection (needs Google's servers)

---

### Issue: "Login redirects but no session"

**Solution:**
1. Check if `sidebar/dashboard.php` exists
2. Verify session is being set:
   ```php
   // Add to dashboard.php
   session_start();
   var_dump($_SESSION);
   ```
3. Check session configuration in `php.ini`

---

## ✅ Success Criteria

Both features are fully functional when:

- [x] Can create admin account successfully
- [x] Account saved to database with hashed password
- [x] Can login with created account
- [x] Session created on successful login
- [x] Redirects to dashboard after login
- [x] reCAPTCHA works on both pages
- [x] Error messages are clear and helpful
- [x] Validation works (empty fields, password mismatch, etc.)
- [x] Security features work (rate limiting, account lockout)

---

## 📝 Notes

1. **Database Port:** The system tries port 3306 first (XAMPP default), then falls back to port 3000 if specified
2. **Username Field:** The username field in create-admin form is optional and not stored (database doesn't have username column)
3. **reCAPTCHA:** Using test keys for development - always passes. Replace with production keys before going live
4. **Password:** Minimum 6 characters (can be strengthened later)
5. **Email:** Used as unique identifier for login (no username in database)

---

**Last Updated:** After fixing all functionality issues






