-- ============================================
-- Fix Admin Account Duplication Issue
-- This script fixes the foreign key constraint and removes duplication
-- ============================================

-- Step 1: Drop the existing foreign key constraint
-- ============================================
ALTER TABLE admin_user 
DROP FOREIGN KEY IF EXISTS admin_user_ibfk_1;

-- Step 2: Modify user_id column to allow NULL
-- ============================================
ALTER TABLE admin_user 
MODIFY COLUMN user_id INT DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)';

-- Step 3: Recreate foreign key with ON DELETE SET NULL (instead of CASCADE)
-- This allows admin_user to exist independently of users table
-- ============================================
ALTER TABLE admin_user 
ADD CONSTRAINT admin_user_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Step 4: Update existing admin_user records to set user_id to NULL
-- This removes the dependency on users table for existing admins
-- ============================================
UPDATE admin_user SET user_id = NULL WHERE user_id IS NOT NULL;

-- Step 5: Optional - Remove duplicate admin records from users table
-- Only run this if you want to clean up duplicates
-- WARNING: This will delete admin accounts from users table
-- ============================================
-- DELETE FROM users WHERE user_type = 'admin' AND id IN (
--     SELECT user_id FROM admin_user WHERE user_id IS NOT NULL
-- );

-- Verification queries:
-- ============================================
-- Check admin_user table structure
-- DESCRIBE admin_user;

-- Check foreign key constraints
-- SELECT 
--     CONSTRAINT_NAME,
--     TABLE_NAME,
--     COLUMN_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME,
--     DELETE_RULE
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = DATABASE()
-- AND TABLE_NAME = 'admin_user'
-- AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check admin accounts
-- SELECT id, user_id, name, email, role, status FROM admin_user;

