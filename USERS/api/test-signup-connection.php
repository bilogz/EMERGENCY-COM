<?php
/**
 * Test Signup Connection
 * This simulates what happens during signup to see where accounts are being saved
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Signup Connection</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection FAILED!\n";
    echo "This means signups won't work!\n";
    exit;
}

echo "✓ Database connection successful\n\n";

try {
    // Show which database we're connected to
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $dbName = $stmt->fetch()['db'];
    echo "Connected to database: $dbName\n";
    
    // Get connection host
    $stmt = $pdo->query("SELECT @@hostname as hostname");
    $hostname = $stmt->fetch()['hostname'];
    echo "Database hostname: $hostname\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "❌ 'users' table does NOT exist!\n";
        echo "Run: /USERS/db_setup.php\n";
        exit;
    }
    
    echo "✓ 'users' table exists\n\n";
    
    // Count accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "Total accounts in this database: $count\n\n";
    
    if ($count > 0) {
        echo "Recent accounts:\n";
        $stmt = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC LIMIT 10");
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($accounts as $acc) {
            echo "  - ID: {$acc['id']}, Name: {$acc['name']}, Email: {$acc['email']}, Phone: {$acc['phone']}, Created: {$acc['created_at']}\n";
        }
    } else {
        echo "⚠️  No accounts found in this database.\n";
        echo "\nThis means either:\n";
        echo "1. No one has signed up yet\n";
        echo "2. Signups are going to a different database\n";
    }
    
    echo "\n=== Test INSERT (simulating signup) ===\n";
    
    // Try a test insert
    $testData = [
        'name' => 'Test User ' . date('Y-m-d H:i:s'),
        'email' => 'test_' . time() . '@test.com',
        'phone' => '+63' . rand(9000000000, 9999999999),
        'nationality' => 'Filipino',
        'district' => 'district1',
        'barangay' => 'Test Barangay',
        'house_number' => '#123',
        'street' => 'Test Street',
        'address' => '#123 Test Street, Test Barangay, Quezon City',
        'status' => 'active'
    ];
    
    $sql = "INSERT INTO users (name, email, phone, nationality, district, barangay, house_number, street, address, status, created_at) 
            VALUES (:name, :email, :phone, :nationality, :district, :barangay, :house_number, :street, :address, :status, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($testData)) {
        $testId = $pdo->lastInsertId();
        echo "✓ Test INSERT successful! ID: $testId\n";
        
        // Verify it was inserted
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$testId]);
        $inserted = $stmt->fetch();
        
        if ($inserted) {
            echo "✓ Verified: Account exists in database\n";
            echo "  Name: {$inserted['name']}\n";
            echo "  Email: {$inserted['email']}\n";
            echo "  Phone: {$inserted['phone']}\n";
        }
        
        // Clean up test account
        echo "\nCleaning up test account...\n";
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$testId]);
        echo "✓ Test account deleted\n";
    } else {
        echo "❌ Test INSERT failed!\n";
        print_r($stmt->errorInfo());
    }
    
    echo "\n✅ Test complete!\n";
    echo "\nIf INSERT worked, signups should work too.\n";
    echo "If you see accounts listed above, they're in THIS database.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nSQL State: " . $e->getCode() . "\n";
}

echo "</pre>";
?>

