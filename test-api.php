<?php
/**
 * Test Translation API
 * Quick test to see what's happening
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Translation API Test</h1>";
echo "<pre>";

// Test 1: Check if translation-config.php loads
echo "=== Test 1: Loading Config ===\n";
require_once 'USERS/api/translation-config.php';
echo "✓ Config loaded\n";
echo "LibreTranslate URL: " . LIBRETRANSLATE_URL . "\n";
echo "Cache Days: " . TRANSLATION_CACHE_DAYS . "\n\n";

// Test 2: Check database connection
echo "=== Test 2: Database Connection ===\n";
require_once 'ADMIN/api/db_connect.php';
if ($pdo) {
    echo "✓ Database connected\n\n";
} else {
    echo "✗ Database connection failed\n\n";
}

// Test 3: Check if translation_cache table exists
echo "=== Test 3: Translation Cache Table ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'translation_cache'");
    if ($stmt->rowCount() > 0) {
        echo "✓ translation_cache table exists\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM translation_cache");
        $result = $stmt->fetch();
        echo "  Cached translations: " . $result['count'] . "\n\n";
    } else {
        echo "✗ translation_cache table does NOT exist\n";
        echo "  Run: http://emergency-comm.alertaraqc.com/ADMIN/api/setup-translation-cache.php\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check if LibreTranslate is accessible
echo "=== Test 4: LibreTranslate Server ===\n";
$ch = curl_init('http://localhost:5000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ LibreTranslate is running on http://localhost:5000\n\n";
} else {
    echo "✗ LibreTranslate is NOT running (HTTP $httpCode)\n";
    echo "  Start it: Double-click START_LIBRETRANSLATE.bat\n\n";
}

// Test 5: Test translation function
echo "=== Test 5: Translation Function ===\n";
$testText = "Hello World";
$translated = translateWithLibre($testText, 'en', 'es');
echo "Original: $testText\n";
echo "Translated (ES): $translated\n";
if ($translated !== $testText) {
    echo "✓ Translation working!\n\n";
} else {
    echo "✗ Translation failed (returned same text)\n\n";
}

// Test 6: Check supported_languages table
echo "=== Test 6: Supported Languages ===\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM supported_languages WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "Active languages in database: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT language_code, language_name FROM supported_languages WHERE is_active = 1 AND language_code = 'es'");
    $spanish = $stmt->fetch();
    if ($spanish) {
        echo "✓ Spanish (es) is active: " . $spanish['language_name'] . "\n\n";
    } else {
        echo "✗ Spanish (es) is NOT in database\n\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 7: Full API test
echo "=== Test 7: Full API Test ===\n";
$_GET['lang'] = 'es';
ob_start();
include 'USERS/api/get-translations.php';
$apiResponse = ob_get_clean();
echo "API Response:\n";
$json = json_decode($apiResponse, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "Language: " . ($json['language_code'] ?? 'N/A') . "\n";
    echo "Translations count: " . (isset($json['translations']) ? count($json['translations']) : 0) . "\n";
    echo "Auto-translated: " . ($json['auto_translated'] ? 'true' : 'false') . "\n";
    if (isset($json['message'])) {
        echo "Message: " . $json['message'] . "\n";
    }
} else {
    echo "✗ Invalid JSON response\n";
    echo "Raw response: " . substr($apiResponse, 0, 200) . "\n";
}

echo "</pre>";
?>

