-- ============================================
-- ADMIN_USER TABLE
-- Dedicated table for admin accounts with role-based access control
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
-- MIGRATION: Copy existing admin users from users table to admin_user table
-- ============================================
INSERT INTO admin_user (user_id, name, username, email, password, role, status, created_at)
SELECT 
    id as user_id,
    name,
    username,
    email,
    password,
    'super_admin' as role, -- First admin becomes super_admin
    status,
    created_at
FROM users 
WHERE user_type = 'admin' 
AND NOT EXISTS (
    SELECT 1 FROM admin_user WHERE admin_user.user_id = users.id
);

-- ============================================
-- Set first admin as super_admin if none exists
-- ============================================
-- Use variables to avoid MySQL error #1093 (can't update table in FROM clause)
SET @super_admin_exists = (SELECT COUNT(*) FROM admin_user WHERE role = 'super_admin' AND status = 'active');

SET @first_admin_id = (SELECT id FROM admin_user ORDER BY created_at ASC LIMIT 1);

UPDATE admin_user 
SET role = 'super_admin', status = 'active'
WHERE id = @first_admin_id
AND @super_admin_exists = 0
AND @first_admin_id IS NOT NULL;

