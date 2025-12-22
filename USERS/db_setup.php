<?php
// Database setup script for USERS module
// This creates all necessary tables for user management and email OTP verification

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'api/db_connect.php';

try {
    // ============================================
    // CREATE USERS TABLE (if not exists)
    // ============================================
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
        email VARCHAR(255) DEFAULT NULL COMMENT 'Email address (optional)',
        phone VARCHAR(20) NOT NULL COMMENT 'Mobile phone number (primary identifier)',
        password VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password (optional, for email/password login)',
        
        -- Address Information
        barangay VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name',
        house_number VARCHAR(50) DEFAULT NULL COMMENT 'House or unit number',
        address TEXT DEFAULT NULL COMMENT 'Complete address',
        
        -- Account Status
        status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended, banned',
        email_verified TINYINT(1) DEFAULT 0 COMMENT 'Email verification status',
        phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Phone verification status',
        verification_date DATETIME DEFAULT NULL COMMENT 'Date when phone was verified',
        
        -- User Type
        user_type VARCHAR(20) DEFAULT 'citizen' COMMENT 'citizen, admin, guest',
        
        -- Profile Information
        profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile picture',
        date_of_birth DATE DEFAULT NULL,
        gender VARCHAR(10) DEFAULT NULL COMMENT 'male, female, other',
        
        -- Emergency Information
        emergency_contact_name VARCHAR(255) DEFAULT NULL,
        emergency_contact_phone VARCHAR(20) DEFAULT NULL,
        emergency_contact_relation VARCHAR(50) DEFAULT NULL,
        
        -- Metadata
        last_login DATETIME DEFAULT NULL COMMENT 'Last login timestamp',
        login_count INT DEFAULT 0 COMMENT 'Total number of logins',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Indexes
        UNIQUE KEY unique_phone (phone),
        UNIQUE KEY unique_email (email),
        INDEX idx_name (name),
        INDEX idx_phone (phone),
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_user_type (user_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createUsersTable);
    echo "✓ Users table ready\n";
    
    // ============================================
    // CREATE OTP_VERIFICATIONS TABLE (if not exists)
    // ============================================
    $createOtpTable = "
    CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL COMMENT 'User ID if user exists',
        phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number to verify (legacy)',
        email VARCHAR(255) DEFAULT NULL COMMENT 'Email to verify',
        otp_code VARCHAR(10) NOT NULL COMMENT '6-digit OTP code',
        purpose VARCHAR(50) DEFAULT 'login' COMMENT 'login, registration, password_reset, phone_change, email_change',
        status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, verified, expired, used',
        attempts INT DEFAULT 0 COMMENT 'Number of verification attempts',
        max_attempts INT DEFAULT 5 COMMENT 'Maximum allowed attempts',
        expires_at DATETIME NOT NULL COMMENT 'OTP expiration time',
        verified_at DATETIME DEFAULT NULL COMMENT 'When OTP was verified',
        ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address of requester',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        -- Indexes
        INDEX idx_email (email),
        INDEX idx_phone (phone),
        INDEX idx_user_id (user_id),
        INDEX idx_otp_code (otp_code),
        INDEX idx_status (status),
        INDEX idx_expires_at (expires_at),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createOtpTable);
    echo "✓ OTP Verifications table ready\n";
    
    // ============================================
    // CHECK AND ADD EMAIL COLUMN IF MISSING
    // ============================================
    // Check if email column exists
    $checkEmailColumn = "SHOW COLUMNS FROM users LIKE 'email'";
    $result = $pdo->query($checkEmailColumn)->fetchAll();
    
    if (empty($result)) {
        // Email column doesn't exist, add it
        $addEmailColumn = "ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER name";
        $pdo->exec($addEmailColumn);
        echo "✓ Added email column to users table\n";
    } else {
        echo "✓ Email column already exists in users table\n";
    }
    
    // ============================================
    // ADD UNIQUE CONSTRAINT IF MISSING
    // ============================================
    // Check if unique email constraint exists
    $checkConstraint = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'email' AND CONSTRAINT_NAME != 'PRIMARY'";
    $result = $pdo->query($checkConstraint)->fetchAll();
    
    if (empty($result)) {
        try {
            $addConstraint = "ALTER TABLE users ADD UNIQUE KEY unique_email (email)";
            $pdo->exec($addConstraint);
            echo "✓ Added unique constraint on email column\n";
        } catch (Exception $e) {
            echo "ℹ Email constraint not added (may already exist): " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ Email unique constraint already exists\n";
    }
    
    echo "\n✅ Database setup complete! All tables are ready.\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    die();
}
?>
