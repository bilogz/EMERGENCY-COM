<?php
/**
 * Add username column to users table
 * Run this script if the users table doesn't have a username column
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Add Username Column to Users Table</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . ($dbError ?? 'Unknown error') . "\n";
    exit;
}

echo "✓ Database connection successful!\n\n";

try {
    // Check if username column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✓ Username column already exists in users table\n";
    } else {
        echo "⚠ Username column not found. Adding it now...\n";
        
        // Add username column
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login' AFTER name");
        echo "✓ Username column added successfully\n";
        
        // Add index
        try {
            $pdo->exec("ALTER TABLE users ADD INDEX idx_username (username)");
            echo "✓ Index added for username column\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                echo "⚠ Could not add index: " . $e->getMessage() . "\n";
            } else {
                echo "✓ Index already exists\n";
            }
        }
    }
    
    // Verify column structure
    echo "\n--- Users Table Structure ---\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n✓ Script completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>

