-- Emergency Communication System - User Database Schema
-- Run this SQL script to create all necessary tables for user management
-- This complements the main database_schema.sql in ADMIN/api/

-- ============================================
-- USERS TABLE
-- ============================================
-- Main table for storing user/citizen information
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

-- ============================================
-- OTP VERIFICATIONS TABLE
-- ============================================
-- Stores OTP codes for email verification (phone retained for backward compatibility)
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'User ID if user exists',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number to verify (legacy)',
    email VARCHAR(255) DEFAULT NULL COMMENT 'Email to verify',
    otp_code VARCHAR(10) NOT NULL COMMENT '6-digit OTP code',
    purpose VARCHAR(50) DEFAULT 'login' COMMENT 'login, registration, password_reset, phone_change,email_change',
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
    INDEX idx_created_at (created_at),
    
    -- Foreign key (optional, can be NULL for new registrations)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER SESSIONS TABLE
-- ============================================
-- Tracks user sessions for better security and management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL COMMENT 'Unique session token',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL COMMENT 'Browser/device information',
    device_type VARCHAR(50) DEFAULT NULL COMMENT 'mobile, desktop, tablet',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Geographic location if available',
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, expired, revoked',
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL COMMENT 'Session expiration time',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER PREFERENCES TABLE
-- ============================================
-- Stores user preferences and settings
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Notification Preferences
    sms_notifications TINYINT(1) DEFAULT 1 COMMENT 'Enable SMS notifications',
    email_notifications TINYINT(1) DEFAULT 1 COMMENT 'Enable email notifications',
    push_notifications TINYINT(1) DEFAULT 1 COMMENT 'Enable push notifications',
    
    -- Alert Preferences
    alert_categories TEXT DEFAULT NULL COMMENT 'Comma-separated: weather,earthquake,fire,etc',
    preferred_language VARCHAR(10) DEFAULT 'en' COMMENT 'en, tl, ceb, etc',
    alert_priority VARCHAR(20) DEFAULT 'all' COMMENT 'all, high, critical',
    
    -- Display Preferences
    theme VARCHAR(20) DEFAULT 'light' COMMENT 'light, dark, auto',
    timezone VARCHAR(50) DEFAULT 'Asia/Manila',
    
    -- Privacy Settings
    profile_visibility VARCHAR(20) DEFAULT 'private' COMMENT 'public, private, friends',
    share_location TINYINT(1) DEFAULT 0 COMMENT 'Allow location sharing',
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER ACTIVITY LOGS TABLE
-- ============================================
-- Tracks user activities for audit and security
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'NULL for guest users',
    activity_type VARCHAR(50) NOT NULL COMMENT 'login, logout, profile_update, password_change, etc',
    description TEXT DEFAULT NULL COMMENT 'Activity description',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'success' COMMENT 'success, failed, blocked',
    metadata JSON DEFAULT NULL COMMENT 'Additional activity data',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EMERGENCY CONTACTS TABLE
-- ============================================
-- Stores emergency contacts for users
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(255) DEFAULT NULL,
    relationship VARCHAR(50) DEFAULT NULL COMMENT 'family, friend, neighbor, etc',
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'Primary emergency contact',
    priority INT DEFAULT 1 COMMENT 'Contact priority (1=highest)',
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_is_primary (is_primary),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER LOCATIONS TABLE
-- ============================================
-- Stores user location history (for emergency services)
CREATE TABLE IF NOT EXISTS user_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT DEFAULT NULL COMMENT 'Resolved address from coordinates',
    accuracy DECIMAL(10, 2) DEFAULT NULL COMMENT 'Location accuracy in meters',
    source VARCHAR(50) DEFAULT 'gps' COMMENT 'gps, network, manual',
    is_current TINYINT(1) DEFAULT 0 COMMENT 'Current location flag',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_is_current (is_current),
    INDEX idx_created_at (created_at),
    INDEX idx_location (latitude, longitude),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASSWORD RESET TOKENS TABLE
-- ============================================
-- Stores password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL COMMENT 'Reset token',
    purpose VARCHAR(50) DEFAULT 'password_reset' COMMENT 'password_reset, email_verification',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, used, expired',
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER DEVICES TABLE
-- ============================================
-- Tracks user devices for push notifications
CREATE TABLE IF NOT EXISTS user_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL COMMENT 'Unique device identifier',
    device_type VARCHAR(50) DEFAULT NULL COMMENT 'ios, android, web',
    device_name VARCHAR(255) DEFAULT NULL COMMENT 'Device model/name',
    push_token TEXT DEFAULT NULL COMMENT 'Push notification token',
    fcm_token TEXT DEFAULT NULL COMMENT 'Firebase Cloud Messaging token',
    apns_token TEXT DEFAULT NULL COMMENT 'Apple Push Notification token',
    is_active TINYINT(1) DEFAULT 1,
    last_active DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY unique_device (user_id, device_id),
    INDEX idx_user_id (user_id),
    INDEX idx_device_type (device_type),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER SUBSCRIPTIONS TABLE (Enhanced)
-- ============================================
-- Links users to their alert subscriptions (if not already exists)
-- This complements the subscriptions table in the main schema
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT DEFAULT NULL COMMENT 'Alert category ID',
    subscription_type VARCHAR(50) DEFAULT 'all' COMMENT 'all, category, custom',
    channels TEXT DEFAULT NULL COMMENT 'Comma-separated: sms,email,push',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES alert_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INITIAL DATA / DEFAULTS
-- ============================================

-- Create default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123'
INSERT INTO users (name, email, phone, password, user_type, status, phone_verified, email_verified) 
VALUES (
    'System Administrator',
    'admin@emergency.com',
    '+639000000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    1,
    1
) ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- CLEANUP PROCEDURES (Optional)
-- ============================================
-- These can be run periodically to clean up expired data

-- Clean expired OTPs (run daily)
-- DELETE FROM otp_verifications WHERE expires_at < NOW() AND status = 'pending';

-- Clean expired sessions (run daily)
-- DELETE FROM user_sessions WHERE expires_at < NOW() AND status = 'active';

-- Clean expired password reset tokens (run daily)
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW() AND status = 'pending';

-- Archive old activity logs (run monthly, keep last 6 months)
-- DELETE FROM user_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);








