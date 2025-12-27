<?php
/**
 * Test Different Database Connection Options
 * This script tests various connection configurations to find the working one
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Diagnostic</h1>";
echo "<pre>";

$user = 'root';
$pass = 'YsqnXk6q#145';
$db = 'emer_comm_test';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Test different connection configurations
$configs = [
    ['host' => 'localhost', 'port' => 3306, 'name' => 'localhost:3306'],
    ['host' => '127.0.0.1', 'port' => 3306, 'name' => '127.0.0.1:3306'],
    ['host' => 'localhost', 'port' => null, 'name' => 'localhost (no port)'],
    ['host' => 'alertaraqc.com', 'port' => 3306, 'name' => 'alertaraqc.com:3306'],
    ['host' => 'localhost', 'port' => 3307, 'name' => 'localhost:3307'],
    ['host' => '127.0.0.1', 'port' => 3307, 'name' => '127.0.0.1:3307'],
];

$workingConfig = null;

foreach ($configs as $config) {
    echo "\n--- Testing: {$config['name']} ---\n";
    
    try {
        if ($config['port']) {
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset=$charset";
        } else {
            $dsn = "mysql:host={$config['host']};charset=$charset";
        }
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Test if we can query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        
        echo "✓ Connection successful!\n";
        echo "  MySQL Version: $version\n";
        
        // Try to use the database
        try {
            $pdo->exec("USE `$db`");
            echo "✓ Database '$db' exists and accessible\n";
            
            // List tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "✓ Found " . count($tables) . " tables\n";
            
        } catch (PDOException $e) {
            echo "⚠ Database '$db' doesn't exist or not accessible: " . $e->getMessage() . "\n";
            echo "  But connection to MySQL server works!\n";
        }
        
        $workingConfig = $config;
        echo "\n✅ WORKING CONFIGURATION FOUND!\n";
        echo "Use this in db_connect.php:\n";
        echo "  \$host = '{$config['host']}';\n";
        if ($config['port']) {
            echo "  \$port = {$config['port']};\n";
        } else {
            echo "  // No port needed\n";
        }
        break;
        
    } catch (PDOException $e) {
        echo "✗ Connection failed: " . $e->getMessage() . "\n";
    }
}

if (!$workingConfig) {
    echo "\n\n❌ No working configuration found.\n";
    echo "\nPossible issues:\n";
    echo "1. MySQL server is not running\n";
    echo "2. MySQL is not configured to accept connections\n";
    echo "3. Firewall is blocking connections\n";
    echo "4. Wrong credentials\n";
    echo "\nSince phpMyAdmin works, check:\n";
    echo "- What host does phpMyAdmin use? (check config.inc.php)\n";
    echo "- Is MySQL configured for remote access?\n";
    echo "- Check MySQL bind-address in my.cnf\n";
}

echo "</pre>";
?>

