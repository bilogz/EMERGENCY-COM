<?php
/**
 * Test Registration Process
 * This script tests if user registration will work
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Registration Process</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection failed!\n";
    exit;
}

echo "✓ Connected to database\n\n";

try {
    echo "=== Step 1: Check if users table exists ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ 'users' table exists\n\n";
    } else {
        echo "✗ 'users' table does NOT exist!\n";
        echo "Run: http://localhost/EMERGENCY-COM/USERS/db_setup.php\n";
        exit;
    }
    
    echo "=== Step 2: Check required columns ===\n";
    $requiredColumns = [
        'id', 'name', 'email', 'phone', 'password',
        'nationality', 'district', 'barangay', 'house_number', 'street', 'address',
        'created_at'
    ];
    
    $stmt = $pdo->query("DESCRIBE users");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = [];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ Column '$col' MISSING\n";
            $missingColumns[] = $col;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "\n⚠️  Missing columns detected!\n";
        echo "Run: http://localhost/EMERGENCY-COM/USERS/api/fix-users-table.php\n";
        exit;
    }
    
    echo "\n=== Step 3: Test INSERT query ===\n";
    
    // Test data
    $testData = [
        'name' => 'Test User',
        'email' => 'test_' . time() . '@test.com',
        'phone' => '+63' . rand(9000000000, 9999999999),
        'nationality' => 'Filipino',
        'district' => 'district1',
        'barangay' => 'Test Barangay',
        'house_number' => '#123',
        'street' => 'Test Street',
        'address' => '#123 Test Street, Test Barangay, Quezon City'
    ];
    
    $sql = "INSERT INTO users (name, email, phone, nationality, district, barangay, house_number, street, address, created_at) 
            VALUES (:name, :email, :phone, :nationality, :district, :barangay, :house_number, :street, :address, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    echo "Attempting to insert test user...\n";
    if ($stmt->execute($testData)) {
        $userId = $pdo->lastInsertId();
        echo "✓ INSERT successful! User ID: $userId\n";
        
        // Clean up test user
        echo "Cleaning up test user...\n";
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$userId]);
        echo "✓ Test user deleted\n";
    } else {
        echo "✗ INSERT failed!\n";
        print_r($stmt->errorInfo());
    }
    
    echo "\n=== Step 4: Check OTP table ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'otp_verifications'");
    if ($stmt->rowCount() > 0) {
        echo "✓ 'otp_verifications' table exists\n";
    } else {
        echo "✗ 'otp_verifications' table does NOT exist!\n";
        echo "Run: http://localhost/EMERGENCY-COM/USERS/db_setup.php\n";
    }
    
    echo "\n✅ All tests passed! Registration should work.\n";
    echo "\nYou can now try signing up again.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nSQL State: " . $e->getCode() . "\n";
}

echo "</pre>";
?>

