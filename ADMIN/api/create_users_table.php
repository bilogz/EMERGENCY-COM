<?php
/**
 * Create Users Table - Direct Fix
 * This script creates the users table directly
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = 'root';
$pass = 'YsqnXk6q#145';
$dbName = 'emer_comm_test';
$charset = 'utf8mb4';

echo "<h1>Create Users Table</h1>";
echo "<pre>";

// Try multiple connection methods
$connectionAttempts = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'alertaraqc.com', 'port' => 3306],
];

$pdo = null;

foreach ($connectionAttempts as $attempt) {
    try {
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "✓ Connected to MySQL server at {$attempt['host']}:{$attempt['port']}\n\n";
        break;
    } catch (PDOException $e) {
        echo "✗ Failed to connect to {$attempt['host']}:{$attempt['port']}\n";
    }
}

if ($pdo === null) {
    echo "\n✗ Could not connect to MySQL server.\n";
    echo "</pre>";
    exit;
}

try {
    // Select the database
    $pdo->exec("USE `$dbName`");
    echo "✓ Using database '$dbName'\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "⚠ Users table already exists. Checking structure...\n";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasUsername = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'username') {
                $hasUsername = true;
                break;
            }
        }
        
        if (!$hasUsername) {
            echo "⚠ Username column missing. Adding it...\n";
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login' AFTER name");
                $pdo->exec("ALTER TABLE users ADD INDEX idx_username (username)");
                echo "✓ Username column added\n";
            } catch (PDOException $e) {
                echo "✗ Error adding username column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ Users table structure is correct\n";
        }
    } else {
        echo "Creating users table...\n";
        
        // Create users table
        $createUsersTable = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL COMMENT 'Full name of the user',
            username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login',
            email VARCHAR(255) DEFAULT NULL COMMENT 'Email address (optional)',
            phone VARCHAR(20) DEFAULT NULL COMMENT 'Mobile phone number',
            password VARCHAR(255) DEFAULT NULL COMMENT 'Hashed password',
            status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, suspended, pending_approval',
            user_type VARCHAR(20) DEFAULT 'citizen' COMMENT 'citizen, admin, guest',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_phone (phone),
            INDEX idx_username (username),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($createUsersTable);
        echo "✓ Users table created successfully!\n";
    }
    
    // Verify the table
    echo "\n--- Verifying Users Table ---\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    foreach ($columns as $column) {
        $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] !== null ? " DEFAULT '{$column['Default']}'" : '';
        echo "  - {$column['Field']} ({$column['Type']}) $null$default\n";
    }
    
    // Test insert (optional - just to verify)
    echo "\n--- Testing Table ---\n";
    try {
        $testStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $testStmt->execute();
        $count = $testStmt->fetch()['count'];
        echo "✓ Table is accessible. Current row count: $count\n";
    } catch (PDOException $e) {
        echo "✗ Error accessing table: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Users table is ready!\n";
    echo "\nYou can now use create-admin.php to create admin accounts.\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    // Try to provide helpful error message
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "\n⚠ The database '$dbName' doesn't exist.\n";
        echo "Creating it now...\n";
        try {
            $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");
            echo "✓ Database created. Please refresh this page to create the table.\n";
        } catch (PDOException $e2) {
            echo "✗ Could not create database: " . $e2->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>


