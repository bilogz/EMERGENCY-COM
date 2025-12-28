<?php
/**
 * Check Registered Accounts
 * This script shows all registered user accounts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Check Registered Accounts</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection failed!\n";
    exit;
}

echo "✓ Connected to database\n\n";

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "⚠️  'users' table does NOT exist yet!\n";
        echo "\nRun this to create it:\n";
        echo "http://localhost/EMERGENCY-COM/USERS/db_setup.php\n";
        exit;
    }
    
    echo "✓ 'users' table exists\n\n";
    
    // Check table structure
    echo "=== Table Structure ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns: ";
    $columnNames = array_column($columns, 'Field');
    echo implode(", ", $columnNames) . "\n\n";
    
    // Count total user accounts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    
    echo "=== Registered User Accounts ===\n";
    echo "Total user accounts: $count\n\n";
    
    if ($count > 0) {
        // Show all users with all details
        $stmt = $pdo->query("SELECT id, name, email, phone, nationality, district, barangay, house_number, street, address, status, user_type, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "List of all registered accounts:\n";
        echo str_repeat("=", 120) . "\n";
        
        foreach ($users as $index => $user) {
            echo "\nAccount #" . ($index + 1) . "\n";
            echo str_repeat("-", 120) . "\n";
            echo "ID: {$user['id']}\n";
            echo "Name: " . ($user['name'] ?? 'N/A') . "\n";
            echo "Email: " . ($user['email'] ?? 'N/A') . "\n";
            echo "Phone: " . ($user['phone'] ?? 'N/A') . "\n";
            
            if (!empty($user['nationality'])) echo "Nationality: {$user['nationality']}\n";
            if (!empty($user['district'])) echo "District: {$user['district']}\n";
            if (!empty($user['barangay'])) echo "Barangay: {$user['barangay']}\n";
            if (!empty($user['house_number'])) echo "House Number: {$user['house_number']}\n";
            if (!empty($user['street'])) echo "Street: {$user['street']}\n";
            if (!empty($user['address'])) echo "Address: {$user['address']}\n";
            
            echo "Status: " . ($user['status'] ?? 'N/A') . "\n";
            echo "User Type: " . ($user['user_type'] ?? 'N/A') . "\n";
            echo "Registered: " . ($user['created_at'] ?? 'N/A') . "\n";
            echo str_repeat("-", 120) . "\n";
        }
        
        echo "\nTotal: $count account(s)\n";
    } else {
        echo "⚠️  No accounts registered yet.\n";
        echo "\nSign up here:\n";
        echo "http://localhost/EMERGENCY-COM/USERS/signup.php\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

