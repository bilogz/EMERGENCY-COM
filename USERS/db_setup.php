<?php
// Database setup script for USERS module
// This creates all necessary tables for user management and email OTP verification

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'api/db_connect.php';

// Check if database connection was successful
if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection failed!\n";
    echo "Please check your database configuration in api/db_connect.php\n";
    die();
}

try {
    // Show which database we're connected to
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $dbName = $stmt->fetch()['db'];
    echo "✓ Connected to database: $dbName\n";
    echo "✓ Using remote database connection\n\n";
    // ============================================
    // CREATE USERS TABLE (if not exists)
    // ============================================
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
        email VARCHAR(255) DEFAULT NULL COMMENT 'Email address (optional)',
        phone VARCHAR(20) NOT NULL COMMENT 'Mobile phone number (primary identifier)',
        
        -- Personal Information
        nationality VARCHAR(100) DEFAULT NULL COMMENT 'User nationality',
        
        -- Address Information
        district VARCHAR(50) DEFAULT NULL COMMENT 'District in Quezon City',
        barangay VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name',
        house_number VARCHAR(50) DEFAULT NULL COMMENT 'House or unit number',
        street VARCHAR(255) DEFAULT NULL COMMENT 'Street name',
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
    // ============================================
    // ADD MISSING COLUMNS TO EXISTING USERS TABLE
    // ============================================
    $columnsToAdd = [
        'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'User nationality'",
        'district' => "VARCHAR(50) DEFAULT NULL COMMENT 'District in Quezon City'",
        'barangay' => "VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name'",
        'house_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'House or unit number'",
        'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'",
        'address' => "TEXT DEFAULT NULL COMMENT 'Complete address'"
    ];
    
    foreach ($columnsToAdd as $columnName => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = ?");
        $stmt->execute([$columnName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN `$columnName` $definition");
                echo "✓ Added column: $columnName\n";
            } catch (PDOException $e) {
                echo "⚠ Could not add column $columnName: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column '$columnName' already exists\n";
        }
    }
    
    echo "\n✅ Database setup complete! All tables are ready.\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    die();
}
?>
