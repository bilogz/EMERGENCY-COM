<?php
/**
 * Get Gemini/Google AI API Key
 * Returns the stored Gemini API key from database
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

try {
    if ($pdo === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed',
            'apiKey' => null
        ]);
        exit();
    }
    
    // Get Gemini API key from database
    $stmt = $pdo->prepare("SELECT api_key FROM integration_settings WHERE source = 'gemini' OR source = 'google_ai' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $apiKey = $result['api_key'] ?? null;
    
    if ($apiKey && !empty($apiKey)) {
        echo json_encode([
            'success' => true,
            'apiKey' => $apiKey,
            'message' => 'API key found'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Google AI API key not configured. Please set it up.',
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

