<?php
/**
 * Show Current Database Connection
 * This shows which database the USERS module is currently connected to
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Current Database Connection</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection FAILED!\n";
    exit;
}

try {
    // Get database info
    $stmt = $pdo->query("SELECT DATABASE() as db, @@hostname as hostname, USER() as user");
    $info = $stmt->fetch();
    
    echo "✓ Connected!\n\n";
    echo "Database Name: {$info['db']}\n";
    echo "Database Host: {$info['hostname']}\n";
    echo "Database User: {$info['user']}\n\n";
    
    if (strpos($info['hostname'], 'alertaraqc.com') !== false || strpos($info['hostname'], 'alertaraqc') !== false) {
        echo "✅ Connected to REMOTE database (alertaraqc.com)\n";
        echo "   All signups will be saved here.\n";
    } else {
        echo "⚠️  Connected to LOCAL database (localhost)\n";
        echo "   Signups are being saved locally, NOT on remote server!\n";
        echo "\n   To fix: Check db_connect.php configuration.\n";
    }
    
    // Count accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "\nTotal accounts in THIS database: $count\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

