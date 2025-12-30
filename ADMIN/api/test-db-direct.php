<?php
/**
 * Direct Database Connection Test
 * Tests database connection with detailed error messages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Direct Database Connection Test</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }</style>";

// Load config
require_once __DIR__ . '/config.env.php';

$dbConfig = getDatabaseConfig();

echo "<h2>Database Configuration</h2>";
echo "<pre>";
echo "Primary Database:\n";
echo "  Host: " . $dbConfig['primary']['host'] . "\n";
echo "  Port: " . $dbConfig['primary']['port'] . "\n";
echo "  Database: " . $dbConfig['primary']['name'] . "\n";
echo "  User: " . $dbConfig['primary']['user'] . "\n";
echo "  Password: " . (empty($dbConfig['primary']['pass']) ? "(empty)" : "(set)") . "\n";
echo "\nFallback Database:\n";
echo "  Host: " . $dbConfig['fallback']['host'] . "\n";
echo "  Port: " . $dbConfig['fallback']['port'] . "\n";
echo "  Database: " . $dbConfig['fallback']['name'] . "\n";
echo "  User: " . $dbConfig['fallback']['user'] . "\n";
echo "  Password: " . (empty($dbConfig['fallback']['pass']) ? "(empty)" : "(set)") . "\n";
echo "</pre>";

// Test connection attempts
$connectionAttempts = [
    ['name' => 'Primary', 'config' => $dbConfig['primary']],
    ['name' => 'Fallback', 'config' => $dbConfig['fallback']],
];

foreach ($connectionAttempts as $attempt) {
    echo "<h2>Testing " . $attempt['name'] . " Connection</h2>";
    
    $config = $attempt['config'];
    
    // Test 1: Connect without database
    echo "<h3>Step 1: Connect to MySQL Server</h3>";
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "<p class='success'>✓ Successfully connected to MySQL server</p>";
        
        // Test 2: Check if database exists
        echo "<h3>Step 2: Check if Database Exists</h3>";
        $dbName = $config['name'];
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
        $dbExists = $stmt->fetch();
        
        if ($dbExists) {
            echo "<p class='success'>✓ Database '$dbName' exists</p>";
        } else {
            echo "<p class='error'>✗ Database '$dbName' does NOT exist</p>";
            echo "<p class='info'>Attempting to create database...</p>";
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<p class='success'>✓ Database '$dbName' created successfully</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Failed to create database: " . htmlspecialchars($e->getMessage()) . "</p>";
                continue;
            }
        }
        
        // Test 3: Connect to the database
        echo "<h3>Step 3: Connect to Database</h3>";
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            echo "<p class='success'>✓ Successfully connected to database '$dbName'</p>";
            
            // Test 4: Run a query
            echo "<h3>Step 4: Test Query</h3>";
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<p class='success'>✓ Query test successful</p>";
            
            // Test 5: Check admin_user table
            echo "<h3>Step 5: Check admin_user Table</h3>";
            $stmt = $pdo->query("SHOW TABLES LIKE 'admin_user'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ admin_user table exists</p>";
                
                // Check for the account
                $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM admin_user WHERE email = ?");
                $stmt->execute(['joecel519@gmail.com']);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    echo "<p class='success'>✓ Admin account found</p>";
                    echo "<pre>";
                    print_r($admin);
                    echo "</pre>";
                } else {
                    echo "<p class='error'>✗ Admin account NOT FOUND (email: joecel519@gmail.com)</p>";
                }
            } else {
                echo "<p class='info'>⚠ admin_user table does not exist</p>";
            }
            
            echo "<hr>";
            echo "<p class='success'><strong>✓ " . $attempt['name'] . " connection is WORKING!</strong></p>";
            break; // Success, no need to try fallback
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Failed to connect to database: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Failed to connect to MySQL server</p>";
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        // Provide helpful suggestions
        if (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'No connection') !== false) {
            echo "<p class='info'><strong>💡 Suggestion:</strong> MySQL server is not running. Please start MySQL in XAMPP Control Panel.</p>";
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<p class='info'><strong>💡 Suggestion:</strong> Wrong username or password. Check your config.local.php file.</p>";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "<p class='info'><strong>💡 Suggestion:</strong> Database does not exist. It will be created automatically if permissions allow.</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If MySQL is not running: Start it in XAMPP Control Panel</li>";
echo "<li>If connection works: <a href='check-and-update-password.php'>Update Password</a></li>";
echo "<li>If still having issues: Check XAMPP error logs</li>";
echo "</ul>";

echo "<p><a href='test-login-components.php'>Back to Component Test</a> | <a href='../login.php'>Back to Login</a></p>";
?>

