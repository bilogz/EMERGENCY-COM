<?php
/**
 * Database Connection Test Script
 * Use this to diagnose database connection issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
echo "<pre>";

// Database configuration
$db   = 'emer_comm_test';
$user = 'root';
$pass = 'YsqnXk6q#145';
$charset = 'utf8mb4';

$connectionAttempts = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
];

$connected = false;
$lastError = '';

foreach ($connectionAttempts as $attempt) {
    echo "Attempting connection to {$attempt['host']}:{$attempt['port']}...\n";
    try {
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};charset=$charset";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ Connected to MySQL server!\n\n";
        
        // Check if database exists
        echo "Checking if database '$db' exists...\n";
        $stmt = $pdo->query("SHOW DATABASES LIKE '$db'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Database '$db' exists!\n\n";
            
            // Try to use the database
            $pdo->exec("USE `$db`");
            echo "✓ Successfully connected to database '$db'!\n\n";
            
            // Check if tables exist
            echo "Checking for required tables...\n";
            $tables = ['users', 'otp_verifications'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo "✓ Table '$table' exists\n";
                } else {
                    echo "✗ Table '$table' does NOT exist\n";
                }
            }
            
            $connected = true;
        } else {
            echo "✗ Database '$db' does NOT exist!\n";
            echo "\nTo create the database, run:\n";
            echo "CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        }
        break;
    } catch (PDOException $e) {
        $lastError = $e->getMessage();
        echo "✗ Failed: " . $e->getMessage() . "\n\n";
    }
}

if (!$connected) {
    echo "\n=== DIAGNOSIS ===\n";
    echo "Database connection failed!\n\n";
    echo "Common issues:\n";
    echo "1. MySQL service is not running in XAMPP\n";
    echo "   → Start MySQL from XAMPP Control Panel\n";
    echo "2. Wrong database credentials\n";
    echo "   → Check username/password in db_connect.php\n";
    echo "3. Database doesn't exist\n";
    echo "   → Create database 'emer_comm_test' in phpMyAdmin\n";
    echo "\nLast error: $lastError\n";
}

echo "</pre>";
?>

