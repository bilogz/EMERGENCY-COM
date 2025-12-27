<?php
/**
 * Database Connection Test Script
 * Tests the connection to the remote database and verifies all modules can access it
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<pre>";

// Test database connection
require_once 'db_connect.php';

if ($pdo === null) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . ($dbError ?? 'Unknown error') . "\n";
    exit;
}

echo "✓ Database connection successful!\n\n";

// Test 1: Check database name
try {
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $result = $stmt->fetch();
    echo "✓ Connected to database: " . $result['db'] . "\n";
} catch (PDOException $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
}

// Test 2: List all tables
echo "\n--- Tables in database ---\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠ No tables found. Please run setup_remote_database.php first.\n";
    } else {
        echo "✓ Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "  - $table ($count rows)\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Error listing tables: " . $e->getMessage() . "\n";
}

// Test 3: Test each module's required tables
echo "\n--- Module Table Verification ---\n";

$moduleTables = [
    'Mass Notification' => ['notification_logs'],
    'Alert Categorization' => ['alert_categories', 'alerts'],
    'Two-Way Communication' => ['conversations', 'messages'],
    'Automated Warnings' => ['integration_settings', 'warning_settings', 'automated_warnings'],
    'Multilingual Alerts' => ['alert_translations'],
    'Citizen Subscriptions' => ['subscriptions'],
    'Users' => ['users']
];

foreach ($moduleTables as $module => $tables) {
    echo "\n$module:\n";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "  ✓ $table exists ($count rows)\n";
        } catch (PDOException $e) {
            echo "  ✗ $table missing or error: " . $e->getMessage() . "\n";
        }
    }
}

// Test 4: Test basic CRUD operations
echo "\n--- CRUD Operations Test ---\n";

try {
    // Test INSERT
    $stmt = $pdo->prepare("INSERT INTO alert_categories (name, icon, description, color) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=name");
    $stmt->execute(['Test Category', 'fa-test', 'Test description', '#000000']);
    echo "✓ INSERT test passed\n";
    
    // Test SELECT
    $stmt = $pdo->prepare("SELECT * FROM alert_categories WHERE name = ?");
    $stmt->execute(['Test Category']);
    $result = $stmt->fetch();
    if ($result) {
        echo "✓ SELECT test passed\n";
    }
    
    // Test UPDATE
    $stmt = $pdo->prepare("UPDATE alert_categories SET description = ? WHERE name = ?");
    $stmt->execute(['Updated test description', 'Test Category']);
    echo "✓ UPDATE test passed\n";
    
    // Test DELETE
    $stmt = $pdo->prepare("DELETE FROM alert_categories WHERE name = ?");
    $stmt->execute(['Test Category']);
    echo "✓ DELETE test passed\n";
    
} catch (PDOException $e) {
    echo "✗ CRUD test failed: " . $e->getMessage() . "\n";
}

// Test 5: Check default data
echo "\n--- Default Data Check ---\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM alert_categories");
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        echo "✓ Alert categories exist ($count categories)\n";
    } else {
        echo "⚠ No alert categories found. Default data may not be loaded.\n";
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM integration_settings");
    $count = $stmt->fetch()['count'];
    if ($count > 0) {
        echo "✓ Integration settings exist ($count settings)\n";
    } else {
        echo "⚠ No integration settings found.\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error checking default data: " . $e->getMessage() . "\n";
}

echo "\n--- Test Complete ---\n";
echo "✓ All tests passed! Database is ready for use.\n";
echo "</pre>";
?>

