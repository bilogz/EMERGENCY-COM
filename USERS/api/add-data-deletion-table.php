<?php
/**
 * Add Data Deletion Requests Table
 * Run this script to create the table for tracking data deletion requests
 */

require_once 'db_connect.php';

try {
    // Create data_deletion_requests table
    $sql = "CREATE TABLE IF NOT EXISTS data_deletion_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facebook_id VARCHAR(255) NOT NULL COMMENT 'Facebook user ID who requested deletion',
        user_id INT DEFAULT NULL COMMENT 'Local user ID if found',
        confirmation_code VARCHAR(50) NOT NULL COMMENT 'Unique confirmation code for tracking',
        status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, completed, failed',
        requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        
        UNIQUE KEY unique_confirmation_code (confirmation_code),
        INDEX idx_facebook_id (facebook_id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_requested_at (requested_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Created data_deletion_requests table\n";
    
    echo "\n✅ Database migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
