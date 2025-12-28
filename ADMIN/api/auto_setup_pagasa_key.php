<?php
/**
 * Auto Setup PAGASA API Key
 * Run this script to automatically configure the PAGASA API key
 * This will fix the "PAGASA API key not configured" error
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Auto Setup PAGASA API Key</h1>";
echo "<pre>";

if ($pdo === null) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . ($dbError ?? 'Unknown error') . "\n";
    echo "</pre>";
    exit;
}

echo "✓ Database connection successful!\n\n";

// Default OpenWeather API key (replace with your own if needed)
$defaultApiKey = 'f35609a701ba47952fba4fd4604c12c7';

try {
    // Check if API key already exists
    $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'pagasa'");
    $stmt->execute();
    $result = $stmt->fetch();
    $existingKey = $result['api_key'] ?? null;
    
    if ($existingKey && !empty($existingKey)) {
        echo "✓ PAGASA API key already exists: " . substr($existingKey, 0, 10) . "...\n";
        echo "  No changes needed.\n";
    } else {
        echo "⚠ No PAGASA API key found. Setting up default key...\n";
        
        // Insert or update the API key
        $stmt = $pdo->prepare("
            INSERT INTO integration_settings (source, enabled, api_key, api_url, updated_at)
            VALUES ('pagasa', 0, ?, 'https://api.openweathermap.org/data/2.5/', NOW())
            ON DUPLICATE KEY UPDATE api_key = ?, api_url = 'https://api.openweathermap.org/data/2.5/', updated_at = NOW()
        ");
        $stmt->execute([$defaultApiKey, $defaultApiKey]);
        
        echo "✓ PAGASA API key configured successfully!\n";
        echo "  API Key: " . substr($defaultApiKey, 0, 10) . "...\n";
    }
    
    // Verify the setup
    echo "\n--- Verification ---\n";
    $stmt = $pdo->prepare("SELECT source, enabled, api_key, api_url FROM integration_settings WHERE source = 'pagasa'");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if ($settings) {
        echo "Source: " . $settings['source'] . "\n";
        echo "Enabled: " . ($settings['enabled'] ? 'Yes' : 'No') . "\n";
        echo "API Key: " . ($settings['api_key'] ? substr($settings['api_key'], 0, 15) . '...' : 'Not set') . "\n";
        echo "API URL: " . ($settings['api_url'] ?? 'Not set') . "\n";
    }
    
    echo "\n✅ Setup complete!\n";
    echo "\nYou can now use the Weather Monitoring page.\n";
    echo "If you have your own OpenWeather API key, update it in:\n";
    echo "  Automated Warnings > Settings\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>

