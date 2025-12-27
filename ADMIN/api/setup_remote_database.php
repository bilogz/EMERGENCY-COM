<?php
/**
 * Remote Database Setup Script
 * Creates the emer_comm_test database and runs the schema
 * Usage: Run this script once via browser or CLI to set up the database
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user = 'root';
$pass = 'YsqnXk6q#145';
$dbName = 'emer_comm_test';
$charset = 'utf8mb4';

echo "<h1>Database Setup Script</h1>";
echo "<pre>";

// Try multiple connection methods
$connectionAttempts = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'alertaraqc.com', 'port' => 3306],
];

$pdo = null;
$workingHost = null;
$workingPort = null;

foreach ($connectionAttempts as $attempt) {
    try {
        $dsn = "mysql:host={$attempt['host']};port={$attempt['port']};charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        $workingHost = $attempt['host'];
        $workingPort = $attempt['port'];
        echo "✓ Connected to MySQL server at {$attempt['host']}:{$attempt['port']}\n";
        break;
    } catch (PDOException $e) {
        echo "✗ Failed to connect to {$attempt['host']}:{$attempt['port']} - " . $e->getMessage() . "\n";
    }
}

if ($pdo === null) {
    echo "\n✗ Could not connect to MySQL server with any configuration.\n";
    echo "Please run test_db_connection_options.php to find the correct settings.\n";
    echo "</pre>";
    exit;
}

try {
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbName' created or already exists\n";
    
    // Switch to the database
    $pdo->exec("USE `$dbName`");
    echo "✓ Switched to database '$dbName'\n";
    
    // Read and execute schema file
    $schemaFile = __DIR__ . '/database_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Split by semicolons and execute each statement
    // Remove comments and empty lines
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt);
        }
    );
    
    $executed = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠ Warning executing statement: " . substr($statement, 0, 50) . "...\n";
                echo "   Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Executed $executed SQL statements\n";
    
    // Add username column to users table if it doesn't exist (for existing databases)
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
        if ($stmt->rowCount() === 0) {
            echo "\n⚠ Adding username column to users table...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) DEFAULT NULL COMMENT 'Username for login' AFTER name");
            $pdo->exec("ALTER TABLE users ADD INDEX idx_username (username)");
            echo "✓ Username column added successfully\n";
        } else {
            echo "✓ Username column already exists\n";
        }
    } catch (PDOException $e) {
        // Ignore if table doesn't exist yet or column already exists
        if (strpos($e->getMessage(), "doesn't exist") === false && strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "⚠ Warning adding username column: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify tables were created
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n✓ Database setup complete!\n";
    echo "\nCreated tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n✓ Database '$dbName' is ready to use!\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

