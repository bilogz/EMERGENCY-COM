<?php
/**
 * Test AI Settings - Diagnostic script
 * Check if database columns exist and test the getAISettings function
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>AI Settings Diagnostic Test</h2>";
echo "<pre>";

try {
    require_once 'db_connect.php';
    require_once 'secure-api-config.php';
    
    if ($pdo === null) {
        die("❌ Database connection failed!");
    }
    
    echo "✓ Database connected\n\n";
    
    // Check if table exists
    echo "=== Checking table structure ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_warning_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'ai_warning_settings' exists\n\n";
        
        // Get all columns
        $stmt = $pdo->query("DESCRIBE ai_warning_settings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns in table:\n";
        $expectedColumns = [
            'weather_analysis_auto_send',
            'weather_analysis_interval', 
            'weather_analysis_verification_key'
        ];
        
        $foundColumns = [];
        foreach ($columns as $column) {
            $colName = $column['Field'];
            $marker = in_array($colName, $expectedColumns) ? " ⭐ REQUIRED" : "";
            echo "  - {$colName} ({$column['Type']}){$marker}\n";
            if (in_array($colName, $expectedColumns)) {
                $foundColumns[] = $colName;
            }
        }
        
        echo "\n=== Checking for required columns ===\n";
        foreach ($expectedColumns as $col) {
            if (in_array($col, $foundColumns)) {
                echo "✓ Column '{$col}' exists\n";
            } else {
                echo "✗ Column '{$col}' is MISSING!\n";
            }
        }
        
        // Try to fetch settings
        echo "\n=== Testing SELECT query ===\n";
        try {
            $stmt = $pdo->query("SELECT * FROM ai_warning_settings ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                echo "✓ Query successful - Found settings row\n";
                echo "\nSettings data:\n";
                foreach ($settings as $key => $value) {
                    $value = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                    echo "  {$key}: {$value}\n";
                }
                
                // Check if new columns exist in result
                echo "\n=== Checking new columns in result ===\n";
                foreach ($expectedColumns as $col) {
                    if (isset($settings[$col])) {
                        echo "✓ '{$col}' exists in result: " . ($settings[$col] ?? 'NULL') . "\n";
                    } else {
                        echo "✗ '{$col}' NOT in result array (column might not exist in table)\n";
                    }
                }
            } else {
                echo "⚠ Query successful but no settings found (table is empty)\n";
            }
        } catch (PDOException $e) {
            echo "✗ Query failed: " . $e->getMessage() . "\n";
            echo "Error Code: " . $e->getCode() . "\n";
        }
        
    } else {
        echo "✗ Table 'ai_warning_settings' does NOT exist!\n";
    }
    
    // Test secure config
    echo "\n=== Testing secure config ===\n";
    if (function_exists('getGeminiApiKey')) {
        echo "✓ getGeminiApiKey() function exists\n";
        try {
            $key = getGeminiApiKey();
            if ($key) {
                echo "✓ API key found (length: " . strlen($key) . ")\n";
            } else {
                echo "⚠ API key not set\n";
            }
        } catch (Exception $e) {
            echo "✗ Error getting API key: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ getGeminiApiKey() function does NOT exist\n";
    }
    
    // Test JSON encoding
    echo "\n=== Testing JSON encoding ===\n";
    $testData = [
        'weather_analysis_auto_send' => 0,
        'weather_analysis_interval' => 60,
        'weather_analysis_verification_key' => ''
    ];
    $json = json_encode($testData, JSON_UNESCAPED_UNICODE);
    if ($json) {
        echo "✓ JSON encoding works\n";
        echo "Sample output: " . substr($json, 0, 100) . "...\n";
    } else {
        echo "✗ JSON encoding failed: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n✅ Diagnostic complete!\n";
?>

