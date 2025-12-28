<?php
/**
 * Setup Translation Cache Table
 * Run this once to create the translation cache table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Translation Cache Setup</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .success { color: #4caf50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { color: #2196f3; }
    pre { background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd; }
    h1 { color: #333; }
</style></head><body>";

echo "<h1>🗄️ Translation Cache Setup</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "<span class='error'>✗ Database connection failed!</span>\n";
    echo "</pre></body></html>";
    exit;
}

echo "<span class='success'>✓ Database connection successful!</span>\n\n";

// Create translation cache table
try {
    $sql = "
    CREATE TABLE IF NOT EXISTS translation_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cache_key VARCHAR(32) NOT NULL UNIQUE COMMENT 'MD5 hash of text+source+target',
        source_text TEXT NOT NULL COMMENT 'Original text',
        source_lang VARCHAR(10) NOT NULL COMMENT 'Source language code',
        target_lang VARCHAR(10) NOT NULL COMMENT 'Target language code',
        translated_text TEXT NOT NULL COMMENT 'Translated text',
        translation_method VARCHAR(50) DEFAULT 'api' COMMENT 'Translation method used',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cache_key (cache_key),
        INDEX idx_langs (source_lang, target_lang),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
    echo "<span class='success'>✓ Translation cache table created successfully!</span>\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<span class='info'>ℹ Translation cache table already exists</span>\n";
    } else {
        echo "<span class='error'>✗ Error creating table: " . $e->getMessage() . "</span>\n";
    }
}

// Check table structure
try {
    $stmt = $pdo->query("DESCRIBE translation_cache");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n<span class='success'>✓ Table structure verified:</span>\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (PDOException $e) {
    echo "<span class='error'>✗ Error checking table: " . $e->getMessage() . "</span>\n";
}

// Check if we can insert test data
try {
    $testKey = md5('test' . time());
    $stmt = $pdo->prepare("
        INSERT INTO translation_cache 
        (cache_key, source_text, source_lang, target_lang, translated_text, translation_method)
        VALUES (?, 'Test', 'en', 'fil', 'Pagsusulit', 'test')
    ");
    $stmt->execute([$testKey]);
    
    echo "\n<span class='success'>✓ Test insert successful!</span>\n";
    
    // Clean up test data
    $pdo->prepare("DELETE FROM translation_cache WHERE cache_key = ?")->execute([$testKey]);
    echo "<span class='info'>ℹ Test data cleaned up</span>\n";
} catch (PDOException $e) {
    echo "\n<span class='error'>✗ Test insert failed: " . $e->getMessage() . "</span>\n";
}

// Count existing cache entries
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM translation_cache");
    $result = $stmt->fetch();
    $count = $result['count'];
    echo "\n<span class='info'>ℹ Current cache entries: {$count}</span>\n";
} catch (PDOException $e) {
    echo "\n<span class='error'>✗ Error counting entries: " . $e->getMessage() . "</span>\n";
}

echo "\n<span class='success'>✅ Setup complete!</span>\n";
echo "\nTranslation cache is ready to use.\n";
echo "Translations will be automatically cached to improve performance.\n";

echo "</pre></body></html>";
?>

