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
    
    // Process SQL statements more carefully
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    // Split by semicolon but keep CREATE TABLE statements together
    $statements = [];
    $currentStatement = '';
    $inCreateTable = false;
    
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $currentStatement .= $line . "\n";
        
        // Check if we're in a CREATE TABLE block
        if (stripos($line, 'CREATE TABLE') !== false) {
            $inCreateTable = true;
        }
        
        // End of statement
        if (substr(rtrim($line), -1) === ';' && !$inCreateTable) {
            $stmt = trim($currentStatement);
            if (!empty($stmt) && strlen($stmt) > 10) {
                $statements[] = $stmt;
            }
            $currentStatement = '';
        } elseif (substr(rtrim($line), -1) === ';' && $inCreateTable) {
            // End of CREATE TABLE
            $stmt = trim($currentStatement);
            if (!empty($stmt) && strlen($stmt) > 10) {
                $statements[] = $stmt;
            }
            $currentStatement = '';
            $inCreateTable = false;
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    $successCount = 0;
    $errorCount = 0;
    $skippedCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement)) || strlen(trim($statement)) < 10) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
            // Show progress for important statements
            if (stripos($statement, 'CREATE TABLE') !== false || stripos($statement, 'ALTER TABLE') !== false) {
                $tableName = '';
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                }
                if ($tableName) {
                    echo "<span class='success'>✓ " . ucfirst(str_replace('_', ' ', $tableName)) . " processed</span>\n";
                }
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Check if error is about column/table already existing (which is OK)
            if (strpos($errorMsg, 'Duplicate column') !== false || 
                strpos($errorMsg, 'Duplicate key name') !== false ||
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'Duplicate entry') !== false) {
                $skippedCount++;
                // Don't show skipped messages for cleaner output
            } else {
                echo "<span class='error'>✗ Error in statement " . ($index + 1) . ": " . htmlspecialchars(substr($errorMsg, 0, 100)) . "</span>\n";
                $errorCount++;
            }
        }
    }
    
    echo "<span class='success'>✓ Processed {$successCount} statements successfully</span>\n";
    if ($skippedCount > 0) {
        echo "<span class='info'>ℹ {$skippedCount} statements skipped (already exist)</span>\n";
    }
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
                $missingColumns = [];
                foreach ($requiredColumns as $col) {
                    if (in_array($col, $columns)) {
                        echo "  <span class='success'>  ✓ Column '{$col}' exists</span>\n";
                    } else {
                        echo "  <span class='error'>  ✗ Column '{$col}' missing</span>\n";
                        $missingColumns[] = $col;
                    }
                }
                
                // Try to add missing columns
                if (!empty($missingColumns)) {
                    echo "  <span class='info'>  Attempting to add missing columns...</span>\n";
                    try {
                        if (in_array('translated_by_admin_id', $missingColumns)) {
                            $pdo->exec("ALTER TABLE alert_translations ADD COLUMN translated_by_admin_id INT DEFAULT NULL COMMENT 'Admin who created/updated this translation'");
                            echo "  <span class='success'>  ✓ Added column 'translated_by_admin_id'</span>\n";
                        }
                        if (in_array('translation_method', $missingColumns)) {
                            $pdo->exec("ALTER TABLE alert_translations ADD COLUMN translation_method VARCHAR(20) DEFAULT 'manual' COMMENT 'manual, ai, hybrid'");
                            echo "  <span class='success'>  ✓ Added column 'translation_method'</span>\n";
                        }
                    } catch (PDOException $e) {
                        echo "  <span class='error'>  ✗ Could not add columns: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                    }
                }
            }
        } else {
            echo "<span class='error'>✗ {$description} does not exist</span>\n";
            
            // Try to create missing tables
            if ($table === 'supported_languages') {
                echo "  <span class='info'>  Attempting to create supported_languages table...</span>\n";
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS supported_languages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            language_code VARCHAR(10) NOT NULL UNIQUE COMMENT 'ISO 639-1 or custom code',
                            language_name VARCHAR(100) NOT NULL COMMENT 'Display name',
                            native_name VARCHAR(100) DEFAULT NULL COMMENT 'Native name',
                            flag_emoji VARCHAR(10) DEFAULT NULL COMMENT 'Flag emoji',
                            is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether active',
                            is_ai_supported TINYINT(1) DEFAULT 1 COMMENT 'AI translation available',
                            priority INT DEFAULT 0 COMMENT 'Display priority',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_is_active (is_active),
                            INDEX idx_priority (priority)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "  <span class='success'>  ✓ Created supported_languages table</span>\n";
                    
                    // Now insert languages
                    echo "  <span class='info'>  Inserting languages...</span>\n";
                    require_once __DIR__ . '/multilingual-schema-update.sql';
                    // The INSERT statements will be handled by the main SQL execution
                } catch (PDOException $e) {
                    echo "  <span class='error'>  ✗ Could not create table: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                }
            } elseif ($table === 'translation_activity_logs') {
                echo "  <span class='info'>  Attempting to create translation_activity_logs table...</span>\n";
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS translation_activity_logs (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            admin_id INT NOT NULL COMMENT 'Admin who performed action',
                            action_type VARCHAR(50) NOT NULL COMMENT 'Action type',
                            alert_id INT DEFAULT NULL COMMENT 'Related alert ID',
                            translation_id INT DEFAULT NULL COMMENT 'Related translation ID',
                            source_language VARCHAR(10) DEFAULT NULL,
                            target_language VARCHAR(10) DEFAULT NULL,
                            translation_method VARCHAR(20) DEFAULT NULL COMMENT 'manual, ai, hybrid',
                            success TINYINT(1) DEFAULT 1 COMMENT 'Success status',
                            error_message TEXT DEFAULT NULL COMMENT 'Error if failed',
                            metadata JSON DEFAULT NULL COMMENT 'Additional data',
                            ip_address VARCHAR(45) DEFAULT NULL,
                            user_agent TEXT DEFAULT NULL,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_admin_id (admin_id),
                            INDEX idx_action_type (action_type),
                            INDEX idx_alert_id (alert_id),
                            INDEX idx_translation_id (translation_id),
                            INDEX idx_created_at (created_at)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    echo "  <span class='success'>  ✓ Created translation_activity_logs table</span>\n";
                } catch (PDOException $e) {
                    echo "  <span class='error'>  ✗ Could not create table: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                }
            }
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

