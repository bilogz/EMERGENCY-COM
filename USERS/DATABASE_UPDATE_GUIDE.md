# Database Update Guide

## Overview
This guide explains how to update the users table structure to support all registration features.

## Quick Setup

### Step 1: Run the Database Update Script
Visit this URL in your browser:
```
http://localhost/EMERGENCY-COM/USERS/api/update-users-table.php
```

This script will automatically:
- Add missing columns to the `users` table
- Add indexes for better performance
- Show you the current table structure

### Step 2: Verify the Update
After running the script, you should see these columns in the users table:
- ✅ `password` - For Google OAuth users
- ✅ `google_id` - Google OAuth identifier
- ✅ `barangay` - Barangay in Quezon City
- ✅ `house_number` - House/Unit number
- ✅ `street` - Street name
- ✅ `address` - Full address string
- ✅ `nationality` - User nationality
- ✅ `district` - District (optional)

## Manual SQL Alternative

If you prefer to run SQL directly, execute this:

```sql
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `password` VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password for Google OAuth users',
ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(255) DEFAULT NULL UNIQUE COMMENT 'Google OAuth ID',
ADD COLUMN IF NOT EXISTS `barangay` VARCHAR(100) DEFAULT NULL COMMENT 'Barangay in Quezon City',
ADD COLUMN IF NOT EXISTS `house_number` VARCHAR(50) DEFAULT NULL COMMENT 'House/Unit number',
ADD COLUMN IF NOT EXISTS `street` VARCHAR(255) DEFAULT NULL COMMENT 'Street name',
ADD COLUMN IF NOT EXISTS `address` VARCHAR(500) DEFAULT NULL COMMENT 'Full address string',
ADD COLUMN IF NOT EXISTS `nationality` VARCHAR(100) DEFAULT NULL COMMENT 'User nationality',
ADD COLUMN IF NOT EXISTS `district` VARCHAR(50) DEFAULT NULL COMMENT 'District (optional)';

-- Add index for google_id
CREATE INDEX IF NOT EXISTS `idx_google_id` ON `users` (`google_id`);
```

## Features Enabled After Update

1. **Google OAuth Login/Registration** - Users can sign in with Google
2. **Barangay Selection** - Autocomplete dropdown with all Quezon City barangays
3. **Street Selection** - Autocomplete dropdown with common Quezon City streets
4. **Address Storage** - Full address stored in database
5. **User Profile** - Complete user information stored

## Troubleshooting

### Error: Column already exists
This is normal if you've run the script before. The script checks for existing columns.

### Error: Cannot add unique constraint
If `google_id` column already exists without unique constraint, you may need to:
```sql
ALTER TABLE `users` MODIFY `google_id` VARCHAR(255) DEFAULT NULL UNIQUE;
```

### Database Connection Issues
Make sure your database connection is working:
- Check `USERS/api/db_connect.php` configuration
- Verify database credentials
- Ensure MySQL/MariaDB is running

## Next Steps

After updating the database:
1. Test user registration at `/USERS/signup.php`
2. Test Google OAuth login at `/USERS/login.php`
3. Verify barangay and street autocomplete work
4. Check that addresses are saved correctly

