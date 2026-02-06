-- ============================================
-- EMERGENCY COMMUNICATION SYSTEM
-- COMPLETE DATABASE SCHEMA MIGRATION
-- ============================================
-- This file contains the complete merged database schema for the entire system
-- Merges all tables from emer_comm_test (34 tables) and emer_comm_test_app (30 tables)
-- 
-- USAGE:
-- 1. Create your target database first (or use existing LGU database):
--    CREATE DATABASE IF NOT EXISTS LGU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Select the database:
--    USE LGU;
-- 3. Run this script
-- 
-- Total Tables: 36 (all merged with emergency_comm_ prefix)
-- MySQL Compatible: Yes
-- Target Database: LGU
-- Created: 2024
-- ============================================
-- 
-- NOTE: This script will create all tables with 'emergency_comm_' prefix in LGU database
-- All tables are namespace-isolated with the prefix to avoid conflicts
-- ============================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS LGU 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Select the LGU database
USE LGU;

-- MySQL Compatibility Settings
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Note: This script is compatible with MySQL 5.7+ and MariaDB 10.2+
-- All tables use InnoDB engine with utf8mb4 character set for full Unicode support

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
    email VARCHAR(255) DEFAULT NULL COMMENT 'Email address (optional)',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number',
    password VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password (optional, for email/password login)',
    username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
    
    -- Address Information
    barangay VARCHAR(100) DEFAULT NULL COMMENT 'Barangay name',
    house_number VARCHAR(50) DEFAULT NULL COMMENT 'House or unit number',
    street VARCHAR(255) DEFAULT NULL COMMENT 'Street name',
    district VARCHAR(50) DEFAULT NULL COMMENT 'District name',
    address VARCHAR(500) DEFAULT NULL COMMENT 'Full address',
    
    -- Account Status
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended, banned, pending_approval',
    email_verified TINYINT(1) DEFAULT 0 COMMENT 'Email verification status',
    phone_verified TINYINT(1) DEFAULT 0 COMMENT 'Phone verification status',
    verification_date DATETIME DEFAULT NULL COMMENT 'Date when phone/email was verified',
    
    -- User Type
    user_type VARCHAR(20) DEFAULT 'citizen' COMMENT 'citizen, admin, guest',
    
    -- Profile Information
    profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile picture',
    date_of_birth DATE DEFAULT NULL,
    gender VARCHAR(10) DEFAULT NULL COMMENT 'male, female, other',
    nationality VARCHAR(100) DEFAULT NULL COMMENT 'User nationality',
    google_id VARCHAR(255) DEFAULT NULL COMMENT 'Google OAuth user ID',
    
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
    UNIQUE KEY unique_username (username),
    UNIQUE KEY unique_google_id (google_id),
    INDEX idx_name (name),
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_user_type (user_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ADMIN_USER TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_admin_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'Optional reference to emergency_comm_users table (NULL for standalone admin accounts)',
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
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES emergency_comm_admin_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. OTP VERIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'User ID if user exists',
    phone VARCHAR(20) DEFAULT NULL COMMENT 'Phone number to verify (legacy)',
    email VARCHAR(255) NOT NULL COMMENT 'Email to verify',
    otp_code VARCHAR(10) NOT NULL COMMENT '6-digit OTP code',
    purpose VARCHAR(50) DEFAULT 'login' COMMENT 'login, registration, password_reset, phone_change, email_change, admin_login, admin_create',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, verified, expired, used, failed',
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
    INDEX idx_purpose (purpose),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    INDEX idx_email_purpose (email, purpose),
    
    -- Foreign key (optional, can be NULL for new registrations)
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. ADMIN ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL COMMENT 'Admin user ID from emergency_comm_admin_user table',
    action VARCHAR(100) NOT NULL COMMENT 'Action performed (e.g., login, logout, send_notification, etc.)',
    description TEXT DEFAULT NULL COMMENT 'Detailed description of the action',
    metadata JSON DEFAULT NULL COMMENT 'Additional action data',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address of the admin',
    user_agent TEXT DEFAULT NULL COMMENT 'Browser/user agent information',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES emergency_comm_admin_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ADMIN LOGIN LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL DEFAULT 0 COMMENT 'Admin user ID (0 if admin not found)',
    email VARCHAR(255) NOT NULL,
    login_status VARCHAR(20) NOT NULL COMMENT 'success, failed, blocked',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    logout_at DATETIME DEFAULT NULL,
    session_duration INT DEFAULT NULL COMMENT 'Session duration in seconds',
    INDEX idx_admin_id (admin_id),
    INDEX idx_email (email),
    INDEX idx_login_status (login_status),
    INDEX idx_login_at (login_at)
    -- Note: No foreign key for admin_id=0 cases (failed login attempts for non-existent admins)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. NOTIFICATION LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel VARCHAR(50) NOT NULL COMMENT 'sms, email, pa',
    message TEXT NOT NULL,
    recipient VARCHAR(255) DEFAULT NULL,
    recipients TEXT DEFAULT NULL COMMENT 'Comma-separated list of recipients',
    priority VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, success, failed',
    sent_at DATETIME NOT NULL,
    sent_by VARCHAR(100) DEFAULT 'system',
    ip_address VARCHAR(45) DEFAULT NULL,
    response TEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    INDEX idx_channel (channel),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ALERT CATEGORIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_alert_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'fa-exclamation-triangle',
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#4c8a89',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. INCIDENTS TABLE (Created before alerts for foreign key reference)
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'flood, earthquake, fire, crime, typhoon',
    severity VARCHAR(20) NOT NULL COMMENT 'LOW, MODERATE, EXTREME',
    area VARCHAR(100) NOT NULL COMMENT 'Barangay / City / Zone name',
    confidence DECIMAL(5,2) DEFAULT 100.00 COMMENT 'Confidence percentage (0-100)',
    description TEXT DEFAULT NULL COMMENT 'Additional incident details',
    source VARCHAR(100) DEFAULT 'manual' COMMENT 'manual, automated, sensor',
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, resolved, cancelled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_type (type),
    INDEX idx_severity (severity),
    INDEX idx_area (area),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_severity_type (severity, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ALERTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    incident_id INT DEFAULT NULL COMMENT 'Reference to incidents table',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    content TEXT DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL COMMENT 'Emergency Alert, Warning, Advisory',
    area VARCHAR(100) DEFAULT NULL COMMENT 'Affected area (Barangay/City/Zone)',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Location name/address',
    latitude DECIMAL(10,8) DEFAULT NULL COMMENT 'Latitude coordinate',
    longitude DECIMAL(11,8) DEFAULT NULL COMMENT 'Longitude coordinate',
    source VARCHAR(100) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES emergency_comm_alert_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (incident_id) REFERENCES emergency_comm_incidents(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_incident_id (incident_id),
    INDEX idx_status (status),
    INDEX idx_category_new (category),
    INDEX idx_area_new (area),
    INDEX idx_location (location),
    INDEX idx_coordinates (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. ALERT ACKNOWLEDGMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_alert_acknowledgments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'received' COMMENT 'received, safe, need_help',
    latitude DECIMAL(10,8) DEFAULT NULL COMMENT 'Location when acknowledged',
    longitude DECIMAL(11,8) DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_user (alert_id, user_id),
    FOREIGN KEY (alert_id) REFERENCES emergency_comm_alerts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. ALERT TRANSLATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_alert_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    target_language VARCHAR(10) NOT NULL COMMENT 'en, tl, ceb, etc.',
    translated_title VARCHAR(255) NOT NULL,
    translated_content TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    translated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    translated_by_admin_id INT DEFAULT NULL COMMENT 'Admin who created/updated this translation',
    translation_method VARCHAR(20) DEFAULT 'manual' COMMENT 'manual, ai, hybrid',
    FOREIGN KEY (alert_id) REFERENCES emergency_comm_alerts(id) ON DELETE CASCADE,
    FOREIGN KEY (translated_by_admin_id) REFERENCES emergency_comm_admin_user(id) ON DELETE SET NULL,
    UNIQUE KEY unique_alert_language (alert_id, target_language),
    INDEX idx_language (target_language),
    INDEX idx_status (status),
    INDEX idx_translated_by (translated_by_admin_id),
    INDEX idx_translation_method (translation_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. CONVERSATIONS TABLE (Merged from both databases)
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'User ID (integer) - from emer_comm_test_app',
    user_id_string VARCHAR(255) DEFAULT NULL COMMENT 'User ID (string) - for guest users or legacy support',
    user_name VARCHAR(255) DEFAULT NULL,
    user_email VARCHAR(255) DEFAULT NULL,
    user_phone VARCHAR(50) DEFAULT NULL,
    user_location VARCHAR(255) DEFAULT NULL,
    user_concern VARCHAR(100) DEFAULT NULL,
    is_guest TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, closed, archived',
    last_message TEXT DEFAULT NULL,
    last_message_time DATETIME DEFAULT NULL,
    assigned_to INT DEFAULT NULL COMMENT 'Admin user ID',
    device_info TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_user_id_string (user_id_string),
    INDEX idx_status (status),
    INDEX idx_updated_at (updated_at),
    INDEX idx_ip_address (ip_address),
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES emergency_comm_admin_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    sender_type VARCHAR(20) NOT NULL COMMENT 'admin, citizen, system',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    nonce VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (conversation_id) REFERENCES emergency_comm_conversations(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sent_at (sent_at),
    UNIQUE KEY unique_nonce (nonce)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. CHAT MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id VARCHAR(255) NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    sender_type ENUM('user','admin') NOT NULL,
    message_text TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address when message was sent',
    device_info TEXT COMMENT 'Device info when message was sent',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at),
    INDEX idx_sender_type (sender_type),
    FOREIGN KEY (conversation_id) REFERENCES emergency_comm_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. CHAT QUEUE TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_chat_queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) DEFAULT NULL,
    user_phone VARCHAR(50) DEFAULT NULL,
    user_location VARCHAR(255) DEFAULT NULL,
    user_concern VARCHAR(100) DEFAULT NULL,
    is_guest TINYINT(1) DEFAULT 1,
    message TEXT NOT NULL,
    status ENUM('pending','accepted','closed') DEFAULT 'pending',
    assigned_to INT DEFAULT NULL COMMENT 'Admin user ID',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY idx_conversation_unique (conversation_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (conversation_id) REFERENCES emergency_comm_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES emergency_comm_admin_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. INTEGRATION SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_integration_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL UNIQUE COMMENT 'pagasa, phivolcs',
    enabled TINYINT(1) DEFAULT 0,
    api_key VARCHAR(255) DEFAULT NULL,
    api_url VARCHAR(255) DEFAULT NULL,
    last_sync DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. WARNING SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_warning_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sync_interval INT DEFAULT 15 COMMENT 'Minutes',
    auto_publish TINYINT(1) DEFAULT 0,
    notification_channels TEXT DEFAULT NULL COMMENT 'Comma-separated: sms,email,pa',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. AUTOMATED WARNINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_automated_warnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL COMMENT 'pagasa, phivolcs',
    type VARCHAR(100) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    severity VARCHAR(20) DEFAULT 'medium' COMMENT 'low, medium, high, critical',
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, published, archived',
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    published_at DATETIME DEFAULT NULL,
    INDEX idx_source (source),
    INDEX idx_status (status),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. INCIDENT REPORTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type VARCHAR(50) NOT NULL COMMENT 'fire, flood, injury, crime, other',
    description TEXT DEFAULT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, verified, resolved, false_alarm',
    media_url VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded image/video',
    admin_notes TEXT DEFAULT NULL COMMENT 'Internal notes by responders',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 20. SUBSCRIPTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    categories TEXT DEFAULT NULL COMMENT 'Comma-separated: weather,earthquake,bomb,fire,general',
    channels TEXT DEFAULT NULL COMMENT 'Comma-separated: sms,email,push',
    preferred_language VARCHAR(10) DEFAULT 'en',
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 21. USER SESSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_sessions (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 22. USER PREFERENCES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_preferences (
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
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 23. USER ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_activity_logs (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 24. EMERGENCY CONTACTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_emergency_contacts (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 25. USER LOCATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_locations (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 26. PASSWORD RESET TOKENS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_password_reset_tokens (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 27. USER DEVICES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_devices (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 28. USER SUBSCRIPTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_user_subscriptions (
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
    
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES emergency_comm_alert_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 29. RATE LIMITS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempts INT DEFAULT 1,
    first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 30. TRANSLATION CACHE TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_translation_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(32) NOT NULL UNIQUE COMMENT 'MD5 hash of text+source+target',
    source_text TEXT NOT NULL COMMENT 'Original text',
    source_lang VARCHAR(10) NOT NULL COMMENT 'Source language code',
    target_lang VARCHAR(10) NOT NULL COMMENT 'Target language code',
    translated_text TEXT NOT NULL COMMENT 'Translated text',
    translation_method VARCHAR(50) DEFAULT 'api' COMMENT 'Translation method used',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_langs (source_lang, target_lang),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 31. SUPPORTED LANGUAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_supported_languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL UNIQUE COMMENT 'ISO 639-1 or custom code',
    language_name VARCHAR(100) NOT NULL COMMENT 'Display name',
    native_name VARCHAR(100) DEFAULT NULL COMMENT 'Native name',
    flag_emoji VARCHAR(10) DEFAULT NULL COMMENT 'Flag emoji',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether active',
    is_ai_supported TINYINT(1) DEFAULT 1 COMMENT 'AI translation available',
    priority INT DEFAULT 0 COMMENT 'Display priority',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 32. AI WARNING SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_ai_warning_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gemini_api_key VARCHAR(255) DEFAULT NULL,
    ai_enabled TINYINT(1) DEFAULT 0,
    ai_check_interval INT DEFAULT 30,
    wind_threshold DECIMAL(5,2) DEFAULT 60.00,
    rain_threshold DECIMAL(5,2) DEFAULT 20.00,
    earthquake_threshold DECIMAL(3,1) DEFAULT 5.0,
    warning_types TEXT DEFAULT NULL,
    monitored_areas TEXT DEFAULT NULL,
    ai_channels TEXT DEFAULT NULL,
    weather_analysis_auto_send TINYINT(1) DEFAULT 0,
    weather_analysis_interval INT DEFAULT 60,
    weather_analysis_verification_key VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 33. AUDIT LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    user_id INT DEFAULT NULL,
    performed_by INT DEFAULT NULL COMMENT 'Admin or user who performed the action',
    ip_address VARCHAR(45) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_performed_by (performed_by),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES emergency_comm_users(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES emergency_comm_admin_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 34. EVACUATION CENTERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_evacuation_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT DEFAULT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    capacity INT DEFAULT 0,
    current_occupancy INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active' COMMENT 'active, full, closed, inactive',
    contact_number VARCHAR(20) DEFAULT NULL,
    amenities TEXT DEFAULT NULL COMMENT 'wifi, water, food, medical',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 35. SAFETY GUIDES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_safety_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category_id INT DEFAULT NULL COMMENT 'Links to emergency_comm_alert_categories',
    content TEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    FOREIGN KEY (category_id) REFERENCES emergency_comm_alert_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 36. TRANSLATION ACTIVITY LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS emergency_comm_translation_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL COMMENT 'Admin who performed action',
    action_type VARCHAR(50) NOT NULL COMMENT 'Action type',
    alert_id INT DEFAULT NULL COMMENT 'Related alert ID',
    translation_id INT DEFAULT NULL COMMENT 'Related translation ID',
    source_language VARCHAR(10) DEFAULT NULL,
    target_language VARCHAR(10) DEFAULT NULL,
    translation_method VARCHAR(20) DEFAULT NULL COMMENT 'manual, ai, hybrid',
    success TINYINT(1) DEFAULT 1 COMMENT 'Success status',
    error_message TEXT DEFAULT NULL COMMENT 'Error if failed',
    metadata JSON DEFAULT NULL COMMENT 'Additional data',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_alert_id (alert_id),
    INDEX idx_translation_id (translation_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES emergency_comm_admin_user(id) ON DELETE CASCADE,
    FOREIGN KEY (alert_id) REFERENCES emergency_comm_alerts(id) ON DELETE SET NULL,
    FOREIGN KEY (translation_id) REFERENCES emergency_comm_alert_translations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA INSERTION
-- ============================================

-- Insert default alert categories
INSERT INTO emergency_comm_alert_categories (name, icon, description, color) VALUES
('Weather', 'fa-cloud-rain', 'Weather-related alerts including storms, floods, and typhoons', '#3498db'),
('Earthquake', 'fa-mountain', 'Seismic activity and earthquake warnings', '#e74c3c'),
('Bomb Threat', 'fa-bomb', 'Security threats and bomb alerts', '#c0392b'),
('Fire', 'fa-fire', 'Fire emergencies and fire safety alerts', '#e67e22'),
('General', 'fa-exclamation-triangle', 'General emergency alerts and announcements', '#95a5a6')
ON DUPLICATE KEY UPDATE name=name;

-- Insert default integration settings
INSERT INTO emergency_comm_integration_settings (source, enabled) VALUES
('pagasa', 0),
('phivolcs', 0)
ON DUPLICATE KEY UPDATE source=source;

-- Insert default warning settings
INSERT INTO emergency_comm_warning_settings (sync_interval, auto_publish, notification_channels) VALUES
(15, 0, 'sms,email')
ON DUPLICATE KEY UPDATE id=id;

-- Insert default AI warning settings
INSERT INTO emergency_comm_ai_warning_settings (ai_enabled, ai_check_interval, wind_threshold, rain_threshold, earthquake_threshold) VALUES
(0, 30, 60.00, 20.00, 5.0)
ON DUPLICATE KEY UPDATE id=id;

-- Insert default super admin user
-- Email: joecel519@gmail.com
-- Password: admin123 (hashed with bcrypt)
INSERT INTO emergency_comm_admin_user (name, email, username, password, role, status) VALUES
('Super Admin', 'joecel519@gmail.com', 'joecel519', '$2y$10$7cZmbuRvyeORbIAnQg8JTOljxkzxKzbwIT56EIQtukpABLRe3jTpK', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE email=email;

-- ============================================
-- COMMIT TRANSACTION
-- ============================================
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- SCHEMA CREATION COMPLETE
-- ============================================
-- All 36 tables have been created successfully.
-- 
-- MERGED FROM:
-- - emer_comm_test: 34 tables
-- - emer_comm_test_app: 30 tables
-- - Additional: incidents, conversations (enhanced)
-- 
-- All tables prefixed with: emergency_comm_
-- 
-- Tables Created (36 total):
-- 1. emergency_comm_users
-- 2. emergency_comm_admin_user
-- 3. emergency_comm_otp_verifications
-- 4. emergency_comm_admin_activity_logs
-- 5. emergency_comm_admin_login_logs
-- 6. emergency_comm_notification_logs
-- 7. emergency_comm_alert_categories
-- 8. emergency_comm_incidents
-- 9. emergency_comm_alerts
-- 10. emergency_comm_alert_acknowledgments
-- 11. emergency_comm_alert_translations
-- 12. emergency_comm_conversations
-- 13. emergency_comm_messages
-- 14. emergency_comm_chat_messages
-- 15. emergency_comm_chat_queue
-- 16. emergency_comm_integration_settings
-- 17. emergency_comm_warning_settings
-- 18. emergency_comm_automated_warnings
-- 19. emergency_comm_incident_reports
-- 20. emergency_comm_subscriptions
-- 21. emergency_comm_user_sessions
-- 22. emergency_comm_user_preferences
-- 23. emergency_comm_user_activity_logs
-- 24. emergency_comm_emergency_contacts
-- 25. emergency_comm_user_locations
-- 26. emergency_comm_password_reset_tokens
-- 27. emergency_comm_user_devices
-- 28. emergency_comm_user_subscriptions
-- 29. emergency_comm_rate_limits
-- 30. emergency_comm_translation_cache
-- 31. emergency_comm_supported_languages
-- 32. emergency_comm_ai_warning_settings
-- 33. emergency_comm_audit_log
-- 34. emergency_comm_evacuation_centers
-- 35. emergency_comm_safety_guides
-- 36. emergency_comm_translation_activity_logs
-- 
-- ============================================
-- MIGRATION INSTRUCTIONS
-- ============================================
-- 
-- TO MIGRATE TO LGU DATABASE:
-- 
-- 1. The database will be created automatically if it doesn't exist
--    Or create manually: CREATE DATABASE IF NOT EXISTS LGU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 
-- 2. Select the database:
--    USE LGU;
-- 
-- 3. Run this entire script in MySQL/MariaDB
-- 
-- 4. Verify all tables were created:
--    SHOW TABLES LIKE 'emergency_comm_%';
--    (Should show 36 tables)
-- 
-- 5. Update your application's database configuration to use the new database
-- 
-- COMPATIBILITY:
-- - MySQL 5.7+
-- - MySQL 8.0+
-- - MariaDB 10.2+
-- 
-- All tables use:
-- - Engine: InnoDB
-- - Charset: utf8mb4
-- - Collation: utf8mb4_unicode_ci
-- 
-- MERGED TABLES SUMMARY:
-- - From emer_comm_test: admin_user, audit_log, chat_messages, chat_queue, subscriptions
-- - From emer_comm_test_app: conversations (simplified version merged)
-- - Enhanced: alerts (added location, latitude, longitude), incidents (new)
-- 
-- ============================================
