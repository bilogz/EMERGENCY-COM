<?php
/**
 * Test Remote Database Connection Only
 * This tests if we can connect to the remote database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Remote Database Connection</h2>";
echo "<pre>";

$host = 'alertaraqc.com';
$port = 3306;
$db = 'emer_comm_test';
$user = 'root';
$pass = 'YsqnXk6q#145';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
];

echo "Attempting to connect to remote database...\n";
echo "Host: $host\n";
echo "Database: $db\n";
echo "User: $user\n\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "✓ SUCCESS! Connected to remote database!\n\n";
    
    // Get database info
    $stmt = $pdo->query("SELECT DATABASE() as db, @@hostname as hostname");
    $info = $stmt->fetch();
    
    echo "Database Name: {$info['db']}\n";
    echo "Database Host: {$info['hostname']}\n\n";
    
    // Count accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "Total accounts in remote database: $count\n";
    
    if ($count > 0) {
        echo "\nRecent accounts:\n";
        $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($accounts as $acc) {
            echo "  - {$acc['name']} ({$acc['email']}) - {$acc['created_at']}\n";
        }
    }
    
    echo "\n✅ Remote connection works! The issue is in db_connect.php fallback logic.\n";
    
} catch (PDOException $e) {
    echo "❌ FAILED to connect to remote database!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n\n";
    
    echo "Possible reasons:\n";
    echo "1. Firewall blocking port 3306\n";
    echo "2. Remote MySQL not allowing connections from your IP\n";
    echo "3. Network connectivity issues\n";
    echo "4. Wrong hostname/IP address\n";
    echo "\nIf remote connection fails, accounts will fallback to localhost.\n";
}

echo "</pre>";
?>

