<?php
/**
 * Get Gemini/Google AI API Key
 * Returns the stored Gemini API key from database
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';
require_once 'secure-api-config.php';

try {
    // Get Gemini API key securely (checks config file first, then database)
    $apiKey = getGeminiApiKey();
    
    // Debug: Check config file path
    $configPath = __DIR__ . '/../../USERS/api/config.local.php';
    $configExists = file_exists($configPath);
    
    if ($apiKey && !empty($apiKey)) {
        // Verify key format (should start with AIzaSy)
        if (strpos($apiKey, 'AIzaSy') === 0) {
            echo json_encode([
                'success' => true,
                'apiKey' => $apiKey,
                'message' => 'API key found',
                'source' => $configExists ? 'secure_config' : 'database'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid API key format. Please check your config.local.php file.',
                'apiKey' => null
            ]);
        }
    } else {
        $errorMsg = 'Google AI API key not configured. ';
        if (!$configExists) {
            $errorMsg .= 'Config file not found at: ' . $configPath . '. ';
        }
        $errorMsg .= 'Please set it up in USERS/api/config.local.php or database.';
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'apiKey' => null,
            'config_file_exists' => $configExists,
            'config_path' => $configPath
        ]);
    }
} catch (Exception $e) {
    error_log("Get Gemini Key Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading API key: ' . $e->getMessage(),
        'apiKey' => null
    ]);
}
?>

