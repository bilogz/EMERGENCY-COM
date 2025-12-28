<?php
/**
 * Test Remote Database Connection
 * This will test if the code can connect to the remote database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Remote Database Connection</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection FAILED!\n";
    echo "\nThe code tried to connect but couldn't.\n";
    echo "Check your db_connect.php configuration.\n";
    exit;
}

echo "✓ Database connection successful!\n\n";

try {
    // Check which database we're connected to
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $db = $stmt->fetch()['db'];
    echo "Connected to database: $db\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ 'users' table exists\n";
        
        // Count accounts
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "Total accounts in this database: $count\n";
        
        if ($count > 0) {
            echo "\nRecent accounts:\n";
            $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 5");
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($accounts as $acc) {
                echo "  - {$acc['name']} ({$acc['email']}) - {$acc['created_at']}\n";
            }
        }
    } else {
        echo "✗ 'users' table does NOT exist\n";
        echo "Run: /USERS/db_setup.php to create it\n";
    }
    
    echo "\n✅ Connection test complete!\n";
    echo "\nIf you see this, signups will work on this server.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

