<?php
/**
 * Test config file loading (Safe - No sensitive data exposed)
 * DELETE THIS FILE AFTER TESTING
 */

header('Content-Type: text/plain');

$configFile = __DIR__ . '/config.local.php';

echo "Config File Path: " . $configFile . "\n";
echo "File Exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "\n";
echo "File Readable: " . (is_readable($configFile) ? 'YES' : 'NO') . "\n";
echo "File Size: " . (file_exists($configFile) ? filesize($configFile) : 'N/A') . " bytes\n\n";

if (file_exists($configFile)) {
    echo "=== Testing include ===\n";
    $config = include $configFile;
    echo "Include returned type: " . gettype($config) . "\n";
    
    if ($config === 1) {
        echo "Include returned: 1 (file executed but no return value)\n";
    } elseif ($config === false) {
        echo "Include returned: false (error including file)\n";
    } else {
        echo "Include returned: " . gettype($config) . "\n";
    }
    echo "\n";
    
    if (is_array($config)) {
        echo "=== Config Array Keys ===\n";
        $keys = array_keys($config);
        foreach ($keys as $key) {
            echo "  - " . $key . "\n";
        }
        echo "\n";
        
        echo "=== GOOGLE_CLIENT_ID Check ===\n";
        echo "Key exists: " . (isset($config['GOOGLE_CLIENT_ID']) ? 'YES' : 'NO') . "\n";
        if (isset($config['GOOGLE_CLIENT_ID'])) {
            $clientId = $config['GOOGLE_CLIENT_ID'];
            echo "Value length: " . strlen($clientId) . " characters\n";
            echo "Value empty: " . (empty($clientId) ? 'YES' : 'NO') . "\n";
            echo "Value starts with: " . substr($clientId, 0, 20) . "...\n";
            echo "Value ends with: ..." . substr($clientId, -20) . "\n";
        } else {
            echo "ERROR: GOOGLE_CLIENT_ID key not found in config array!\n";
        }
        echo "\n";
        
        echo "=== GOOGLE_CLIENT_SECRET Check ===\n";
        echo "Key exists: " . (isset($config['GOOGLE_CLIENT_SECRET']) ? 'YES' : 'NO') . "\n";
        if (isset($config['GOOGLE_CLIENT_SECRET'])) {
            $secret = $config['GOOGLE_CLIENT_SECRET'];
            echo "Value length: " . strlen($secret) . " characters\n";
            echo "Value empty: " . (empty($secret) ? 'YES' : 'NO') . "\n";
        }
    } else {
        echo "ERROR: Config is not an array!\n";
        echo "Type: " . gettype($config) . "\n";
    }
} else {
    echo "ERROR: Config file does not exist!\n";
}

