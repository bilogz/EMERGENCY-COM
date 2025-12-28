<?php
/**
 * Simple Database Connection Check
 * This will help diagnose the exact issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Diagnostic</h2>";
echo "<pre>";

// Test 1: Try connecting without database first
echo "=== Test 1: Connect to MySQL Server ===\n";
$user = 'root';
$pass = 'YsqnXk6q#145'; // Try with password
$passEmpty = ''; // Try without password (XAMPP default)

$hosts = ['localhost', '127.0.0.1'];
$connected = false;

foreach ($hosts as $host) {
    echo "\nTrying host: $host\n";
    
    // Try with password
    try {
        $dsn = "mysql:host=$host;port=3306;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        echo "✓ Connected with password!\n";
        $connected = true;
        $workingPass = $pass;
        $workingHost = $host;
        break;
    } catch (PDOException $e) {
        echo "✗ Failed with password: " . $e->getMessage() . "\n";
    }
    
    // Try without password (XAMPP default)
    try {
        $dsn = "mysql:host=$host;port=3306;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $passEmpty);
        echo "✓ Connected WITHOUT password (XAMPP default)!\n";
        $connected = true;
        $workingPass = '';
        $workingHost = $host;
        break;
    } catch (PDOException $e) {
        echo "✗ Failed without password: " . $e->getMessage() . "\n";
    }
}

if (!$connected) {
    echo "\n❌ Cannot connect to MySQL server!\n";
    echo "\nPossible issues:\n";
    echo "1. MySQL service is not running in XAMPP\n";
    echo "   → Open XAMPP Control Panel and click 'Start' for MySQL\n";
    echo "2. Wrong port (check if MySQL is on port 3306)\n";
    echo "3. MySQL is not installed or configured\n";
    exit;
}

echo "\n=== Test 2: Check if database exists ===\n";
$db = 'emer_comm_test';

try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '$db'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '$db' exists!\n";
    } else {
        echo "✗ Database '$db' does NOT exist!\n";
        echo "\nCreating database...\n";
        $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created!\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
}

echo "\n=== Test 3: Connect to database ===\n";
try {
    $pdo->exec("USE `$db`");
    echo "✓ Successfully connected to database '$db'!\n";
    
    // Check tables
    echo "\n=== Test 4: Check tables ===\n";
    $tables = ['users', 'otp_verifications'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' does NOT exist\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "Working configuration:\n";
echo "Host: $workingHost\n";
echo "User: $user\n";
echo "Password: " . ($workingPass ? "'$workingPass'" : "(empty)") . "\n";
echo "Database: $db\n";

if ($workingPass !== 'YsqnXk6q#145') {
    echo "\n⚠️  WARNING: Your db_connect.php has wrong password!\n";
    echo "Update USERS/api/db_connect.php and ADMIN/api/db_connect.php:\n";
    echo "Change: \$pass = 'YsqnXk6q#145';\n";
    echo "To: \$pass = '';\n";
}

echo "</pre>";
?>

