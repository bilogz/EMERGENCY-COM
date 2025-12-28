<?php
/**
 * Fix Users Table - Add Missing Columns
 * This script adds nationality, district, and street columns if they're missing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fix Users Table</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection failed!\n";
    exit;
}

echo "✓ Connected to database\n\n";

try {
    // Check and add missing columns
    $columnsToAdd = [
        'nationality' => "VARCHAR(100) DEFAULT NULL COMMENT 'User nationality'",
        'district' => "VARCHAR(50) DEFAULT NULL COMMENT 'District in Quezon City'",
        'street' => "VARCHAR(255) DEFAULT NULL COMMENT 'Street name'"
    ];
    
    echo "=== Checking for missing columns ===\n\n";
    
    foreach ($columnsToAdd as $columnName => $definition) {
        // Check if column exists using INFORMATION_SCHEMA
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'users' 
                               AND COLUMN_NAME = ?");
        $stmt->execute([$columnName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            echo "Adding column: $columnName... ";
            try {
                $sql = "ALTER TABLE users ADD COLUMN `$columnName` $definition";
                $pdo->exec($sql);
                echo "✓ Added!\n";
            } catch (PDOException $e) {
                echo "✗ Failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Column '$columnName' already exists\n";
        }
    }
    
    echo "\n=== Current table structure ===\n\n";
    
    // Show all columns
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in 'users' table:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n✅ Users table is ready!\n";
    echo "\nYou can now close this page and try signing up again.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

