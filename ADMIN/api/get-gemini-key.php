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
    
    if ($apiKey && !empty($apiKey)) {
        echo json_encode([
            'success' => true,
            'apiKey' => $apiKey,
            'message' => 'API key found',
            'source' => 'secure_config'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Google AI API key not configured. Please set it up in config.local.php or database.',
            'apiKey' => null
        ]);
    }
} catch (PDOException $e) {
    error_log("Get Gemini Key Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'apiKey' => null
    ]);
}
?>

