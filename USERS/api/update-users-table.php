<?php
/**
 * Update Users Table Structure
 * Adds missing columns based on emer_comm_test.sql structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    die("❌ Database connection failed!");
}

echo "<h2>Updating Users Table Structure</h2>";
echo "<pre>";

try {
    // Columns to add if they don't exist
    $columnsToAdd = [
        'password' => "VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password for Google OAuth users'",
        'google_id' => "VARCHAR(255) DEFAULT NULL UNIQUE COMMENT 'Google OAuth ID'",
        'barangay' => "VARCHAR(100) DEFAULT NULL COMMENT 'Barangay in Quezon City'",
        'house_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'House/Unit number'",
        'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'",
        'address' => "VARCHAR(500) DEFAULT NULL COMMENT 'Full address string'",
        'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'User nationality'",
        'district' => "VARCHAR(50) DEFAULT NULL COMMENT 'District (optional)'"
    ];
    
    foreach ($columnsToAdd as $column => $definition) {
        // Check if column exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            try {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `$column` $definition");
                echo "✓ Added column: $column\n";
            } catch (PDOException $e) {
                echo "✗ Failed to add column $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column already exists: $column\n";
        }
    }
    
    // Add index for google_id if it exists
    try {
        $stmt = $pdo->query("SHOW INDEXES FROM users WHERE Key_name = 'idx_google_id'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `users` ADD INDEX `idx_google_id` (`google_id`)");
            echo "✓ Added index for google_id\n";
        }
    } catch (PDOException $e) {
        // Index might already exist or column doesn't exist yet
        echo "Note: Could not add google_id index (may already exist)\n";
    }
    
    echo "\n✅ Users table structure update complete!\n";
    echo "\nCurrent columns:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

