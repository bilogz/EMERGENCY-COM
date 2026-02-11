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
    
    // Debug: show both common config locations
    $adminConfigPath = __DIR__ . '/config.local.php';
    $usersConfigPath = __DIR__ . '/../../USERS/api/config.local.php';
    $adminConfigExists = file_exists($adminConfigPath);
    $usersConfigExists = file_exists($usersConfigPath);
    
    if ($apiKey && !empty($apiKey)) {
        // Verify key format (should start with AIzaSy)
        if (strpos($apiKey, 'AIzaSy') === 0) {
            echo json_encode([
                'success' => true,
                'apiKey' => $apiKey,
                'message' => 'API key found',
                'source' => ($adminConfigExists || $usersConfigExists) ? 'secure_config' : 'database'
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
        if (!$adminConfigExists && !$usersConfigExists) {
            $errorMsg .= 'Config file not found in ADMIN/api or USERS/api. ';
        }
        $errorMsg .= 'Please set it up in ADMIN/api/config.local.php or database.';
        
        echo json_encode([
            'success' => false,
            'message' => $errorMsg,
            'apiKey' => null,
            'config_files' => [
                'admin_api' => ['exists' => $adminConfigExists, 'path' => $adminConfigPath],
                'users_api' => ['exists' => $usersConfigExists, 'path' => $usersConfigPath],
            ]
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

