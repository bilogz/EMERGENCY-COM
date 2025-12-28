<?php
/**
 * Setup Gemini/Google AI API Key
 * Stores the Google AI API key in the database
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Setup Google AI (Gemini) API Key</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . ($dbError ?? 'Unknown error') . "\n";
    echo "</pre>";
    exit;
}

echo "✓ Database connection successful!\n\n";

require_once 'secure-api-config.php';

// Get API key securely (from config file, database, or environment)
$geminiApiKey = getGeminiApiKey();

if (empty($geminiApiKey)) {
    echo "✗ Error: API key not found!\n";
    echo "\nPlease do ONE of the following:\n";
    echo "1. Create USERS/api/config.local.php with your API key\n";
    echo "2. Set GEMINI_API_KEY environment variable\n";
    echo "3. Pass ?api_key=YOUR_KEY in URL (not recommended for production)\n";
    echo "\nExample config.local.php:\n";
    echo "<?php\n";
    echo "return [\n";
    echo "    'AI_API_KEY' => 'your-api-key-here',\n";
    echo "];\n";
    echo "</pre>";
    exit;
}

try {
    // Store API key in database (for backward compatibility)
    // Primary source is config.local.php, but we also store in DB
    storeGeminiApiKeyInDatabase($geminiApiKey);
    
    // Also update using direct query for verification
    $stmt = $pdo->prepare("
        INSERT INTO integration_settings (source, enabled, api_key, api_url, updated_at)
        VALUES ('gemini', 0, ?, 'https://generativelanguage.googleapis.com/v1beta/', NOW())
        ON DUPLICATE KEY UPDATE api_key = ?, api_url = 'https://generativelanguage.googleapis.com/v1beta/', updated_at = NOW()
    ");
    $stmt->execute([$geminiApiKey, $geminiApiKey]);
    
    echo "✓ Google AI (Gemini) API key stored successfully!\n";
    echo "  API Key: " . substr($geminiApiKey, 0, 20) . "...\n";
    
    // Verify the setup
    echo "\n--- Verification ---\n";
    $stmt = $pdo->prepare("SELECT source, enabled, api_key, api_url FROM integration_settings WHERE source = 'gemini'");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if ($settings) {
        echo "Source: " . $settings['source'] . "\n";
        echo "Enabled: " . ($settings['enabled'] ? 'Yes' : 'No') . "\n";
        echo "API Key: " . ($settings['api_key'] ? substr($settings['api_key'], 0, 20) . '...' : 'Not set') . "\n";
        echo "API URL: " . ($settings['api_url'] ?? 'Not set') . "\n";
    }
    
    echo "\n✅ Setup complete!\n";
    echo "\nThe Google AI API key is now configured for AI Weather Analysis.\n";
    echo "Refresh the Weather Monitoring page to use it.\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>

