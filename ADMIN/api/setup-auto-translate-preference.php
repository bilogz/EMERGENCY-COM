<?php
/**
 * Setup Script: Add Auto-Translation Preference
 * Run this once to add the auto_translate_enabled field to user_preferences table
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

try {
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }
    
    $results = [];
    
    // Step 1: Check if column already exists
    $stmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'user_preferences' 
        AND COLUMN_NAME = 'auto_translate_enabled'
    ");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        $results[] = "✓ Column 'auto_translate_enabled' already exists";
    } else {
        // Step 2: Add the column
        $pdo->exec("
            ALTER TABLE user_preferences 
            ADD COLUMN auto_translate_enabled TINYINT(1) DEFAULT 1 
            COMMENT 'Enable AI auto-translation for non-English/Filipino languages' 
            AFTER preferred_language
        ");
        $results[] = "✓ Added column 'auto_translate_enabled' to user_preferences table";
        
        // Step 3: Update existing records
        $stmt = $pdo->exec("
            UPDATE user_preferences 
            SET auto_translate_enabled = 1 
            WHERE auto_translate_enabled IS NULL
        ");
        $results[] = "✓ Updated $stmt existing records with default value (enabled)";
    }
    
    // Step 4: Check if index exists
    $stmt = $pdo->query("
        SELECT INDEX_NAME 
        FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_NAME = 'user_preferences' 
        AND INDEX_NAME = 'idx_auto_translate'
    ");
    $indexExists = $stmt->fetch();
    
    if (!$indexExists) {
        // Create index
        $pdo->exec("CREATE INDEX idx_auto_translate ON user_preferences(auto_translate_enabled)");
        $results[] = "✓ Created index 'idx_auto_translate' for performance";
    } else {
        $results[] = "✓ Index 'idx_auto_translate' already exists";
    }
    
    // Step 5: Verify the column structure
    $stmt = $pdo->query("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            COLUMN_DEFAULT, 
            COLUMN_COMMENT 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'user_preferences' 
        AND COLUMN_NAME = 'auto_translate_enabled'
    ");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 6: Count users with auto-translate enabled
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN auto_translate_enabled = 1 THEN 1 ELSE 0 END) as enabled,
            SUM(CASE WHEN auto_translate_enabled = 0 THEN 1 ELSE 0 END) as disabled
        FROM user_preferences
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto-translation preference setup completed successfully',
        'results' => $results,
        'column_info' => $columnInfo,
        'statistics' => [
            'total_users' => (int)$stats['total'],
            'auto_translate_enabled' => (int)$stats['enabled'],
            'auto_translate_disabled' => (int)$stats['disabled']
        ],
        'next_steps' => [
            '1. Users can now toggle auto-translation in their profile settings',
            '2. The system will respect user preference when translating content',
            '3. Default is ENABLED for all users (can be changed in profile)'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

