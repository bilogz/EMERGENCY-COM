<?php
/**
 * Verify Users Table Structure
 * Shows all columns in the users table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Users Table Structure Verification</h2>";
echo "<pre>";

require_once 'db_connect.php';

if (!isset($pdo) || $pdo === null) {
    echo "❌ Database connection failed!\n";
    exit;
}

echo "✓ Connected to database\n\n";

try {
    echo "=== Users Table Columns ===\n\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total columns: " . count($columns) . "\n\n";
    
    foreach ($columns as $col) {
        echo "Column: {$col['Field']}\n";
        echo "  Type: {$col['Type']}\n";
        echo "  Null: {$col['Null']}\n";
        echo "  Default: " . ($col['Default'] ?? 'NULL') . "\n";
        if ($col['Comment']) {
            echo "  Comment: {$col['Comment']}\n";
        }
        echo "\n";
    }
    
    echo "=== Required Columns Check ===\n\n";
    
    $requiredColumns = [
        'id', 'name', 'email', 'phone', 
        'nationality', 'district', 'barangay', 
        'house_number', 'street', 'address',
        'status', 'created_at', 'updated_at'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $reqCol) {
        if (in_array($reqCol, $existingColumns)) {
            echo "✓ $reqCol exists\n";
        } else {
            echo "✗ $reqCol MISSING\n";
        }
    }
    
    echo "\n✅ Table structure verification complete!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

