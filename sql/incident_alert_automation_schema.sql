-- ============================================
-- INCIDENT ALERT AUTOMATION SYSTEM
-- Database Schema Migration
-- ============================================
-- This migration safely adds tables and columns needed for
-- the Emergency Alert Automation System
-- 
-- SAFE FOR PRODUCTION: Uses IF NOT EXISTS and checks before ALTER
-- Created: 2024
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- 1. INCIDENTS TABLE
-- ============================================
-- Table to store incident reports with severity and confidence
CREATE TABLE IF NOT EXISTS incidents (
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
-- 2. EXTEND ALERTS TABLE (Add new columns safely)
-- ============================================
-- Add incident_id column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'alerts';
SET @columnname = 'incident_id';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT DEFAULT NULL COMMENT \'Reference to incidents table\', ADD INDEX idx_incident_id (', @columnname, '), ADD FOREIGN KEY (', @columnname, ') REFERENCES incidents(id) ON DELETE SET NULL')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add category column (for alert categorization: Emergency Alert, Warning, Advisory)
SET @columnname = 'category';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) DEFAULT NULL COMMENT \'Emergency Alert, Warning, Advisory\' AFTER message, ADD INDEX idx_category_new (', @columnname, ')')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add area column for area-based filtering
SET @columnname = 'area';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(100) DEFAULT NULL COMMENT \'Affected area (Barangay/City/Zone)\' AFTER category, ADD INDEX idx_area_new (', @columnname, ')')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
-- The following have been added:
-- 1. incidents table - stores incident reports
-- 2. alerts.incident_id - links alerts to incidents
-- 3. alerts.category - Emergency Alert, Warning, Advisory
-- 4. alerts.area - area-based filtering
-- 
-- Existing alerts table structure is preserved
-- ============================================
