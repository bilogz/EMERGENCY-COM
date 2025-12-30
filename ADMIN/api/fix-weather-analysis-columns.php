<?php
/**
 * Fix Weather Analysis Columns
 * Adds missing weather analysis columns to ai_warning_settings table
 * Run this script once to update your database structure
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

if ($pdo === null) {
    die("❌ Database connection failed!");
}

echo "<h2>Adding Weather Analysis Columns to ai_warning_settings</h2>";
echo "<pre>";

try {
    $columnsToAdd = [
        'weather_analysis_auto_send' => [
            'type' => 'TINYINT(1)',
            'default' => '0',
            'after' => 'ai_channels'
        ],
        'weather_analysis_interval' => [
            'type' => 'INT',
            'default' => '60',
            'after' => 'weather_analysis_auto_send'
        ],
        'weather_analysis_verification_key' => [
            'type' => 'VARCHAR(255)',
            'default' => 'NULL',
            'after' => 'weather_analysis_interval'
        ]
    ];
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        // Check if column exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'ai_warning_settings' 
                               AND COLUMN_NAME = ?");
        $stmt->execute([$columnName]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "✓ Column '$columnName' already exists\n";
        } else {
            echo "Adding column: $columnName... ";
            try {
                $default = $columnDef['default'] === 'NULL' ? 'DEFAULT NULL' : "DEFAULT {$columnDef['default']}";
                $sql = "ALTER TABLE `ai_warning_settings` 
                        ADD COLUMN `{$columnName}` {$columnDef['type']} {$default} 
                        AFTER `{$columnDef['after']}`";
                $pdo->exec($sql);
                echo "✓ Added successfully!\n";
            } catch (PDOException $e) {
                echo "✗ Failed: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Verifying table structure ===\n\n";
    
    // Show current table structure
    $stmt = $pdo->query("DESCRIBE ai_warning_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns in 'ai_warning_settings' table:\n";
    foreach ($columns as $column) {
        $marker = (in_array($column['Field'], array_keys($columnsToAdd))) ? " ⭐" : "";
        echo "  - {$column['Field']} ({$column['Type']}){$marker}\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now refresh the Automated Warnings page and the error should be resolved.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>

