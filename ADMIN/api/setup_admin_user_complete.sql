-- ============================================
-- COMPLETE ADMIN_USER TABLE SETUP
-- Run this SQL script to create admin_user table
-- and migrate existing admin accounts
-- ============================================

-- Step 1: Create admin_user table
-- ============================================
CREATE TABLE IF NOT EXISTS admin_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'Optional reference to users table (NULL for standalone admin accounts)',
    name VARCHAR(255) NOT NULL COMMENT 'Full name of the admin',
    username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
    email VARCHAR(255) NOT NULL COMMENT 'Email address (unique)',
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    role VARCHAR(20) DEFAULT 'admin' COMMENT 'super_admin, admin, staff',
    status VARCHAR(20) DEFAULT 'pending_approval' COMMENT 'active, inactive, suspended, pending_approval',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number',
    created_by INT DEFAULT NULL COMMENT 'ID of admin who created this account',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    
    -- Indexes
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_username (username),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at),
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admin_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Step 2: Migrate existing admin accounts from users table
-- ============================================
INSERT INTO admin_user (user_id, name, username, email, password, role, status, created_at)
SELECT 
    id as user_id,
    name,
    username,
    email,
    password,
    'admin' as role,
    status,
    created_at
FROM users 
WHERE user_type = 'admin' 
AND NOT EXISTS (
    SELECT 1 FROM admin_user WHERE admin_user.user_id = users.id
);

-- ============================================
-- Step 3: Set first admin as super_admin (if none exists)
-- ============================================
-- Check if super_admin exists, if not set the first admin as super_admin
SET @super_admin_exists = (SELECT COUNT(*) FROM admin_user WHERE role = 'super_admin' AND status = 'active');

SET @first_admin_id = (SELECT id FROM admin_user ORDER BY created_at ASC LIMIT 1);

UPDATE admin_user 
SET role = 'super_admin', status = 'active'
WHERE id = @first_admin_id
AND @super_admin_exists = 0
AND @first_admin_id IS NOT NULL;

-- ============================================
-- Verification Queries (Optional - run to verify)
-- ============================================
-- SELECT COUNT(*) as total_admins FROM admin_user;
-- SELECT id, name, email, role, status FROM admin_user ORDER BY created_at ASC;
-- SELECT role, COUNT(*) as count FROM admin_user GROUP BY role;
-- SELECT status, COUNT(*) as count FROM admin_user GROUP BY status;

