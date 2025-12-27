# Fix "Connection Refused" Error

## Problem
Getting "SQLSTATE[HY000] [2002] Connection refused" error when trying to connect to the database.

## Solution

The issue is that MySQL might not be accepting remote connections, or PHP needs to connect via `localhost` instead of the domain name.

### Step 1: Run Diagnostic Script

First, find the correct connection settings:

```
http://your-domain/EMERGENCY-COM/ADMIN/api/test_db_connection_options.php
```

This script will test different connection configurations and tell you which one works.

### Step 2: Update Connection Settings

I've updated `db_connect.php` to try multiple connection methods automatically:
1. `localhost:3306` (if PHP and MySQL are on same server)
2. `127.0.0.1:3306` (alternative localhost)
3. `alertaraqc.com:3306` (remote connection)

### Step 3: Common Fixes

#### If PHP and MySQL are on the same server:
- Use `localhost` or `127.0.0.1` instead of `alertaraqc.com`
- This is usually the case when phpMyAdmin works

#### If MySQL is on a different server:
1. Check MySQL `bind-address` in `my.cnf`:
   ```
   bind-address = 0.0.0.0  # Allow remote connections
   ```

2. Grant remote access:
   ```sql
   GRANT ALL PRIVILEGES ON emer_comm_test.* TO 'root'@'%' IDENTIFIED BY 'YsqnXk6q#145';
   FLUSH PRIVILEGES;
   ```

3. Check firewall allows port 3306

### Step 4: Manual Configuration

If automatic detection doesn't work, manually edit `EMERGENCY-COM/ADMIN/api/db_connect.php`:

```php
// Use the working configuration from test_db_connection_options.php
$host = 'localhost';  // or '127.0.0.1' or 'alertaraqc.com'
$port = 3306;         // or your MySQL port
```

## Quick Test

After updating, test the connection:
```
http://your-domain/EMERGENCY-COM/ADMIN/api/test_database_connection.php
```

## What Changed

1. ✅ Updated `db_connect.php` to try multiple connection methods
2. ✅ Created `test_db_connection_options.php` diagnostic script
3. ✅ Updated setup scripts to try multiple connection methods
4. ✅ Added connection timeout to prevent hanging

The connection should now work automatically by trying different configurations!

