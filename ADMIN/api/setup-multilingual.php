<?php
/**
 * Setup Script for Enhanced Multilingual Support
 * Run this script to set up the database tables and initial data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Multilingual Setup</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { color: #4caf50; }
    .error { color: #f44336; }
    .info { color: #2196f3; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 30px; }
</style></head><body>";
echo "<h1>🌍 Enhanced Multilingual Support Setup</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "<span class='error'>✗ Database connection failed!</span>\n";
    echo "Error: " . ($dbError ?? 'Unknown error') . "\n";
    echo "</pre></body></html>";
    exit;
}

echo "<span class='success'>✓ Database connection successful!</span>\n\n";

// Read and execute schema update SQL
$schemaFile = __DIR__ . '/multilingual-schema-update.sql';

if (!file_exists($schemaFile)) {
    echo "<span class='error'>✗ Schema file not found: {$schemaFile}</span>\n";
    echo "</pre></body></html>";
    exit;
}

echo "<h2>Step 1: Updating Database Schema</h2>\n";

try {
    $sql = file_get_contents($schemaFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Check if error is about column/table already existing (which is OK)
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "<span class='info'>ℹ " . substr($statement, 0, 50) . "... (already exists, skipping)</span>\n";
                $successCount++;
            } else {
                echo "<span class='error'>✗ Error: " . $e->getMessage() . "</span>\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
                $errorCount++;
            }
        }
    }
    
    echo "<span class='success'>✓ Executed {$successCount} statements successfully</span>\n";
    if ($errorCount > 0) {
        echo "<span class='error'>✗ {$errorCount} errors encountered</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Failed to read or execute schema file: " . $e->getMessage() . "</span>\n";
}

echo "\n<h2>Step 2: Verifying Tables</h2>\n";

$tablesToCheck = [
    'alert_translations' => 'Alert translations table',
    'supported_languages' => 'Supported languages table',
    'translation_activity_logs' => 'Translation activity logs table',
    'admin_activity_logs' => 'Admin activity logs table'
];

foreach ($tablesToCheck as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✓ {$description} exists</span>\n";
            
            // Check for important columns
            $stmt = $pdo->query("SHOW COLUMNS FROM {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if ($table === 'alert_translations') {
                $requiredColumns = ['translated_by_admin_id', 'translation_method'];
                foreach ($requiredColumns as $col) {
                    if (in_array($col, $columns)) {
                        echo "  <span class='success'>  ✓ Column '{$col}' exists</span>\n";
                    } else {
                        echo "  <span class='error'>  ✗ Column '{$col}' missing</span>\n";
                    }
                }
            }
        } else {
            echo "<span class='error'>✗ {$description} does not exist</span>\n";
        }
    } catch (PDOException $e) {
        echo "<span class='error'>✗ Error checking {$table}: " . $e->getMessage() . "</span>\n";
    }
}

echo "\n<h2>Step 3: Checking Supported Languages</h2>\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM supported_languages WHERE is_active = 1");
    $result = $stmt->fetch();
    $activeLanguages = $result['count'] ?? 0;
    
    echo "<span class='success'>✓ Found {$activeLanguages} active languages</span>\n";
    
    if ($activeLanguages > 0) {
        $stmt = $pdo->query("SELECT language_code, language_name, is_ai_supported FROM supported_languages WHERE is_active = 1 ORDER BY priority DESC LIMIT 10");
        $languages = $stmt->fetchAll();
        
        echo "\nSample languages:\n";
        foreach ($languages as $lang) {
            $aiBadge = $lang['is_ai_supported'] ? '🤖 AI' : '';
            echo "  • {$lang['language_name']} ({$lang['language_code']}) {$aiBadge}\n";
        }
    }
} catch (PDOException $e) {
    echo "<span class='error'>✗ Error checking languages: " . $e->getMessage() . "</span>\n";
    echo "<span class='info'>ℹ This is OK if the table doesn't exist yet - it will be created</span>\n";
}

echo "\n<h2>Step 4: Checking AI Translation Service</h2>\n";

try {
    $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && !empty($result['api_key'])) {
        echo "<span class='success'>✓ Gemini API key is configured</span>\n";
        echo "  API Key: " . substr($result['api_key'], 0, 20) . "...\n";
    } else {
        echo "<span class='error'>✗ Gemini API key is not configured</span>\n";
        echo "<span class='info'>ℹ AI translation will not be available until API key is set</span>\n";
        echo "<span class='info'>ℹ Run setup-gemini-key.php to configure it</span>\n";
    }
} catch (PDOException $e) {
    echo "<span class='error'>✗ Error checking API key: " . $e->getMessage() . "</span>\n";
}

echo "\n<h2>Step 5: Testing AI Translation Service</h2>\n";

require_once 'ai-translation-service.php';

try {
    $aiService = new AITranslationService($pdo);
    
    if ($aiService->isAvailable()) {
        echo "<span class='success'>✓ AI Translation Service is available</span>\n";
        
        $supportedLangs = $aiService->getSupportedLanguages();
        echo "  Supported languages: " . count($supportedLangs) . "\n";
    } else {
        echo "<span class='error'>✗ AI Translation Service is not available</span>\n";
        echo "<span class='info'>ℹ Configure Gemini API key to enable AI translation</span>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error testing AI service: " . $e->getMessage() . "</span>\n";
}

echo "\n<h2>✅ Setup Complete!</h2>\n";
echo "\n<span class='success'>The enhanced multilingual support system is now set up.</span>\n";
echo "\nNext steps:\n";
echo "1. Configure Gemini API key (if not done): Run setup-gemini-key.php\n";
echo "2. Access the Multilingual Support page in the admin panel\n";
echo "3. Start translating alerts using AI or manual translation\n";
echo "4. View translation activity logs in the admin panel\n";

echo "</pre></body></html>";
?>

